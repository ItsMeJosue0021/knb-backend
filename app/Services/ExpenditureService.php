<?php

namespace App\Services;

use App\Models\Expenditure;
use App\Models\CashDonation;
use App\Models\GCashDonation;
use Illuminate\Http\UploadedFile;
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
    public function updateExpenditure(int $id, array $data)
    {
        $incomingItems = $data['items'] ?? null;
        unset($data['items']);

        DB::transaction(function () use ($id, $data, $incomingItems) {
            $expenditure = Expenditure::findOrFail($id);

            // Only replace the main attachment if a new file was uploaded
            if (!empty($data['attachment']) && $data['attachment'] instanceof \Illuminate\Http\UploadedFile) {
                $data['attachment'] = $data['attachment']->store('expenditures', 'public');
            } else {
                unset($data['attachment']);
            }

            $expenditure->update($data);

            if (!is_array($incomingItems)) {
                return;
            }

            // Track which existing items stay
            $keptIds = [];

            foreach ($incomingItems as $item) {
                if (!empty($item['id'])) {
                    // Update existing item
                    $model = $expenditure->items()->where('id', $item['id'])->first();
                    if (!$model) {
                        continue; // skip unknown IDs
                    }
                    $update = [
                        'name' => $item['name'] ?? $model->name,
                        'description' => $item['description'] ?? null,
                        'quantity' => $item['quantity'] ?? $model->quantity,
                        'unit_price' => $item['unit_price'] ?? $model->unit_price,
                    ];

                    if (!empty($item['image']) && $item['image'] instanceof UploadedFile) {
                        $update['image'] = $item['image']->store('expenditure_items', 'public');
                    } // else keep existing image path

                    $model->update($update);
                    $keptIds[] = $model->id;
                } else {
                    // Create new item
                    if (!empty($item['image']) && $item['image'] instanceof UploadedFile) {
                        $item['image'] = $item['image']->store('expenditure_items', 'public');
                    } else {
                        unset($item['image']);
                    }
                    $created = $expenditure->items()->create($item);
                    $keptIds[] = $created->id;
                }
            }

            // Optionally delete items not in the incoming list
            $expenditure->items()->whereNotIn('id', $keptIds)->delete();
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
