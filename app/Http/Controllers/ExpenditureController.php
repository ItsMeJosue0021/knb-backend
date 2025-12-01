<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateExpenditureRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateExpenditureRequest;
use App\Services\ExpenditureService;
use Illuminate\Http\Request;
use Exception;

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
    public function index() {
        return response([
            'expenditures' => $this->expenditureService->getAllExpenditures()
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


}
