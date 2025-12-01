<?php

namespace App\Services;

use App\Models\ExpenditureItem;

class ExpenditureItemService {

    /**
     * Retrieve all expenditures items
     * @return mixed
     */
    public function getAllExpenditureItems() {
        return ExpenditureItem::all();
    }

    /**
     * Retrieve expenditure item by id
     *
     * @param int $id
     * @return mixed
     */
    public function getExpenditureItemById(int $id) {
        return ExpenditureItem::findOrFail($id);
    }

    /**
     * Retrieve expenditure items by expenditure id
     *
     * @param int $expenditure_id
     * @return mixed
     */
    public function getExpenditureItemsByExpenditureId(int $expenditure_id) {
        return ExpenditureItem::where('expenditure_id', $expenditure_id)->get();
    }

    /**
     * Saves expenditure item record to database
     *
     * @param array $data
     * @return mixed
     */
   public function saveExpenditureItem(array $data) {
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('expenditure_items', 'public');
        }

        ExpenditureItem::create($data);
   }

    /**
     * Saves expenditure item record to database
     *
     * @param int $id
     * @param array $data
     * @return mixed
     */
    public function updateExpenditureItem(int $id, array $data) {
        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('expenditure_items', 'public');
        }

        ExpenditureItem::findOrFail($id)->update($data);
    }

    /**
     * Deletes expenditure item record from database
     *
     * @param int $id
     * @return mixed
     */
    public function deleteExpenditureItem(int $id) {
        $expenditureItem = ExpenditureItem::findOrFail($id);
        $expenditureItem->delete();
    }
}
