<?php

namespace App\Services;

use App\Models\Expenditure;
use App\Models\CashDonation;
use App\Models\GCashDonation;
use Illuminate\Support\Facades\DB;


class ExpenditureService {

    /**
     * Retrieve all expenditures
     * @return mixed
     */
    public function getAllExpenditures()
    {
        return Expenditure::with('items')
            ->latest()
            ->get();
    }

    /**
     * Saves expenditure record to database
     *
     * @param int $id
     * @return mixed
     */
    public function getExpenditureById(int $id) {
        return Expenditure::findOrFail($id);
    }

    /**
     * Saves expenditure record to database
     *
     * @param array $data
     * @return mixed
     */
    public function saveExpenditure(array $data) {
        if (isset($data['attachment'])) {
            $data['attachment'] = $data['attachment']->store('expenditures', 'public');
        }

        Expenditure::create($data);
    }

    /**
     * Updates expenditure record in database
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateExpenditure(int $id, array $data) {
        $items = $data['items'] ?? null;
        unset($data['items']);

        if (isset($data['attachment'])) {
            $data['attachment'] = $data['attachment']->store('expenditures', 'public');
        }

        DB::transaction(function () use ($id, $data, $items) {
            $expenditure = Expenditure::findOrFail($id);
            $expenditure->update($data);

            if (is_array($items)) {
                // Replace existing items with the incoming set
                $expenditure->items()->delete();
                foreach ($items as $item) {
                    if (isset($item['image'])) {
                        $item['image'] = $item['image']->store('expenditure_items', 'public');
                    }
                    $expenditure->items()->create($item);
                }
            }
        });
    }

    /**
     * Saves expenditure record to database
     *
     * @param int $id
     * @return mixed
     */
    public function deleteExpenditure(int $id) {
        $expenditure = Expenditure::findOrFail($id);
        $expenditure->delete();
    }

    /**
     * Search expenditures by key fields using partial matches.
     *
     * @param string $term
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function searchExpenditures(string $term) {
        return Expenditure::query()
            ->where('reference_number', 'like', "%{$term}%")
            ->orWhere('name', 'like', "%{$term}%")
            ->orWhere('description', 'like', "%{$term}%")
            ->orWhere('amount', 'like', "%{$term}%")
            ->orWhere('payment_method', 'like', "%{$term}%")
            ->get()
            ->load('items');
    }

    public function getExpendituresByDateRange(string $startDate, string $endDate) {
        return Expenditure::whereBetween('date', [$startDate, $endDate])->get();
    }

    public function getTotalExpenditures() {
        return Expenditure::sum('amount');
    }

    public function getTotalMonetaryDonations() {
        $totalGCash = GCashDonation::where('status', 'paid')->sum('amount');
        $totalCash = CashDonation::where('status', 'approved')->sum('amount');
        return $totalGCash + $totalCash;
    }

}
