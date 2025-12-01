<?php

namespace App\Services;

use App\Models\Item;
use App\Models\GoodsDonation;

class ItemService {

    /**
     * Service function for retrieving items by id
     *
     * @param int
     * @return mixed
     */
    public function getItemsByDonationId(int $id) {
        return Item::where('goods_donation_id', $id)->latest()->get();
    }

    public function getItemsById(int $id) {
        return Item::findOrFail($id);
    }

    /**
     * Service function for saving items
     *
     * @param int // id of the model to add the item/s from
     * @param array
     * @return mixed
     */
    public function saveItems(int $id, array $data) {
        $donation = GoodsDonation::findOrFail($id);

        if (isset($data['image'])) {
            $data['image'] = $data['image']->store('items', 'public');
        }

        $item = new Item($data);
        $donation->items()->save($item);
    }

    /**
     * Service function for updating items by id
     *
     * @param int
     * @param array
     * @return mixed
     */
    public function updateItem(int $id, array $data) {
        Item::findOrFail($id)->update($data);
    }

    /**
     * Service function for deleting items by id
     *
     * @param int
     * @return mixed
     */
    public function deleteItem(int $id) {
        $item = Item::findOrFail($id);
        $item->delete();

    }
}
