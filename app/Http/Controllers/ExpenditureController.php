<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExpenditureRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateExpenditureRequest;
use App\Services\ExpenditureService;
use Illuminate\Http\Request;
use Exception;
use App\Models\Expenditure;
use Barryvdh\DomPDF\Facade\Pdf;

class ExpenditureController extends Controller
{

    protected $expenditureService;

    public function __construct(ExpenditureService $expenditureService) {
        $this->expenditureService = $expenditureService;
    }

    /**
     * Retrieve all expenditures
     * @return mixed
     */
    public function index(Request $request) {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $expenditures = Expenditure::with(['items', 'project:id,title,date'])
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('date_incurred', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('date_incurred', '<=', $endDate);
            })
            ->latest()
            ->get();

        return response([
            'expenditures' => $expenditures
        ], 200);
    }

    /**
     * Retrieve a specific expenditure by its id
     * @param int
     * @return mixed
     */
    public function show(int $id) {
        return response([
            'expenditure' => $this->expenditureService->getExpenditureById($id)
        ], 200);
    }

    /**
     * Saves expenditure record to database
     * @param \App\Http\Requests\CreateExpenditureRequest
     * @return mixed
     */
    public function store(CreateExpenditureRequest $request) {
        try {
            $data = $request->validated();
            $this->expenditureService->saveExpenditure($data);
            return response(['message' => 'Expenditure has been recorded!'], 201);
        } catch(Exception $e) {
            return response([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Updates expenditure record to database
     * @param int
     * @param \App\Http\Requests\UpdateExpenditureRequest
     * @return mixed
     */
    public function update(int $id, UpdateExpenditureRequest $request) {
        try {
            $expenditure = Expenditure::findOrFail($id);
            if ($expenditure->source_type !== 'manual') {
                return response([
                    'message' => 'This expense is managed from project liquidation and cannot be edited here.',
                ], 422);
            }

            $data = $request->validated();
            $this->expenditureService->updateExpenditure($id, $data);
            return response(['message' => 'Expenditure has been updated!'], 200);
        } catch(Exception $e) {
            return response([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deletes expenditure record from database
     *
     * @param int
     * @return mixed
     */
    public function destroy(int $id) {
        try {
            $expenditure = Expenditure::findOrFail($id);
            if ($expenditure->source_type !== 'manual') {
                return response([
                    'message' => 'This expense is managed from project liquidation and cannot be deleted here.',
                ], 422);
            }

            $this->expenditureService->deleteExpenditure($id);
            return response(['message' => 'Expenditure has been deleted!'], 200);
        } catch(Exception $e) {
            return response([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Returns total expenditures and monetary donations
     *
     * @return mixed
     */
    public function getTotals() {
        return response([
            'total_expenditures' => $this->expenditureService->getTotalExpenditures(),
            'total_monetary_donations' => $this->expenditureService->getTotalMonetaryDonations()
        ], 200);
    }

    /**
     * Search expenditures by reference number, name, description, amount, or payment method.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request) {
        $term = $request->query('q', '');

        if (trim($term) === '') {
            return response([
                'message' => 'Search term (q) is required.',
                'results' => []
            ], 400);
        }

        $results = $this->expenditureService->searchExpenditures($term);

        return response(['expenditures' => $results], 200);
    }

    public function print(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $expenditures = Expenditure::with('project:id,title,date')
            ->when($startDate, function ($query) use ($startDate) {
                $query->whereDate('date_incurred', '>=', $startDate);
            })
            ->when($endDate, function ($query) use ($endDate) {
                $query->whereDate('date_incurred', '<=', $endDate);
            })
            ->orderBy('date_incurred', 'desc')
            ->get();

        $pdf = Pdf::loadView('expenditures.report', [
            'expenditures' => $expenditures,
            'generatedAt' => now(),
            'startDate' => $startDate,
            'endDate' => $endDate,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('expenditures.pdf');
    }

    public function printExpenditure(int $id)
    {
        $expenditure = Expenditure::with(['items', 'project:id,title,date'])->findOrFail($id);

        $pdf = Pdf::loadView('expenditures.single-report', [
            'expenditure' => $expenditure,
            'items' => $expenditure->items,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        $safeRef = preg_replace('/[^A-Za-z0-9_-]+/', '-', $expenditure->reference_number ?? 'expense');
        $fileName = 'expenditure-' . $expenditure->id . '-' . $safeRef . '.pdf';

        return $pdf->stream($fileName);
    }


}
