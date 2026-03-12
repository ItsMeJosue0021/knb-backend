<?php

namespace App\Http\Controllers;

use App\Models\CashLiquidation;
use App\Models\Expenditure;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CashLiquidationController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);

        $cashLiquidations = CashLiquidation::query()
            ->with('project:id,title,date')
            ->when(!empty($validated['project_id']), function ($query) use ($validated) {
                $query->where('project_id', $validated['project_id']);
            })
            ->latest()
            ->get();

        return response()->json([
            'cash_liquidations' => $cashLiquidations,
        ], 200);
    }

    public function indexByProject(int $projectId)
    {
        $project = Project::findOrFail($projectId);

        $cashLiquidations = $project->cashLiquidations()
            ->latest()
            ->get();

        return response()->json([
            'project_id' => $project->id,
            'cash_liquidations' => $cashLiquidations,
        ], 200);
    }

    public function store(Request $request, int $projectId)
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'date_used' => 'required|date',
            'used_at' => 'required|date',
            'date' => 'required|date',
            'point_person' => 'required|string|max:255',
            'person_responsible' => 'required|string|max:255',
            'receipt' => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:5120',
        ]);

        if ($request->hasFile('receipt')) {
            $validated['receipt'] = $request->file('receipt')->store('cash-liquidations', 'public');
        }

        $validated['project_id'] = $project->id;

        $cashLiquidation = DB::transaction(function () use ($validated) {
            $cashLiquidation = CashLiquidation::create($validated);
            $this->syncExpenseRecord($cashLiquidation);

            return $cashLiquidation;
        });

        return response()->json([
            'message' => 'Cash liquidation saved successfully.',
            'cash_liquidation' => $cashLiquidation,
        ], 201);
    }

    public function show(int $id)
    {
        $cashLiquidation = CashLiquidation::with('project:id,title,date')->findOrFail($id);

        return response()->json([
            'cash_liquidation' => $cashLiquidation,
        ], 200);
    }

    public function update(Request $request, int $id)
    {
        $cashLiquidation = CashLiquidation::findOrFail($id);

        $validated = $request->validate([
            'amount' => 'sometimes|required|numeric|min:0.01',
            'date_used' => 'sometimes|required|date',
            'used_at' => 'sometimes|required|date',
            'date' => 'sometimes|required|date',
            'point_person' => 'sometimes|required|string|max:255',
            'person_responsible' => 'sometimes|required|string|max:255',
            'receipt' => 'nullable|file|mimes:jpeg,jpg,png,webp,pdf|max:5120',
            'remove_receipt' => 'nullable|boolean',
        ]);

        if (($validated['remove_receipt'] ?? false) && !empty($cashLiquidation->receipt)) {
            if (Storage::disk('public')->exists($cashLiquidation->receipt)) {
                Storage::disk('public')->delete($cashLiquidation->receipt);
            }

            $validated['receipt'] = null;
        }

        unset($validated['remove_receipt']);

        if ($request->hasFile('receipt')) {
            if (!empty($cashLiquidation->receipt) && Storage::disk('public')->exists($cashLiquidation->receipt)) {
                Storage::disk('public')->delete($cashLiquidation->receipt);
            }

            $validated['receipt'] = $request->file('receipt')->store('cash-liquidations', 'public');
        }

        DB::transaction(function () use ($cashLiquidation, $validated) {
            $cashLiquidation->update($validated);
            $this->syncExpenseRecord($cashLiquidation->fresh());
        });

        return response()->json([
            'message' => 'Cash liquidation updated successfully.',
            'cash_liquidation' => $cashLiquidation->fresh(),
        ], 200);
    }

    public function destroy(int $id)
    {
        $cashLiquidation = CashLiquidation::findOrFail($id);
        $receiptPath = $cashLiquidation->receipt;

        DB::transaction(function () use ($cashLiquidation) {
            Expenditure::query()
                ->where('source_type', 'project_liquidation')
                ->where('source_id', $cashLiquidation->id)
                ->delete();

            $cashLiquidation->delete();
        });

        if (!empty($receiptPath) && Storage::disk('public')->exists($receiptPath)) {
            Storage::disk('public')->delete($receiptPath);
        }

        return response()->json([
            'message' => 'Cash liquidation deleted successfully.',
        ], 200);
    }

    private function syncExpenseRecord(CashLiquidation $cashLiquidation): Expenditure
    {
        $project = $cashLiquidation->project()->select('id', 'title')->first();
        $projectTitle = $project?->title ?: 'Untitled Project';
        $pointPerson = trim((string) $cashLiquidation->point_person);

        return Expenditure::updateOrCreate(
            [
                'source_type' => 'project_liquidation',
                'source_id' => $cashLiquidation->id,
            ],
            [
                'project_id' => $cashLiquidation->project_id,
                'name' => 'Project Cash Liquidation',
                'description' => 'Auto-generated from project liquidation for ' . $projectTitle . '.',
                'amount' => $cashLiquidation->amount,
                'date_incurred' => $cashLiquidation->date_used,
                'payment_method' => 'Project Liquidation',
                'notes' => $pointPerson !== ''
                    ? 'Recorded from project cash liquidation. Point person: ' . $pointPerson . '.'
                    : 'Recorded from project cash liquidation.',
                'status' => 'recorded',
                'attachment' => $cashLiquidation->receipt,
            ]
        );
    }
}
