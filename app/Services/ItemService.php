<?php

namespace App\Services;

use App\Models\Item;
use App\Models\GoodsDonation;

class ItemService
{

    public function getAllItems(array $filters = [])
    {
        $items = Item::with([
            'categoryModel:id,name',
            'subCategoryModel:id,name'
        ])
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('categoryModel', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subCategoryModel', function ($subcategoryQuery) use ($search) {
                            $subcategoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(isset($filters['category']) && $filters['category'] !== '', function ($query) use ($filters) {
                $query->where('category', $filters['category']);
            })
            ->when(isset($filters['sub_category']) && $filters['sub_category'] !== '', function ($query) use ($filters) {
                $query->where('sub_category', $filters['sub_category']);
            })
            ->when(isset($filters['near_expiration_days']) && (int) $filters['near_expiration_days'] > 0, function ($query) use ($filters) {
                $days = (int) $filters['near_expiration_days'];
                $today = now()->toDateString();
                $until = now()->addDays($days)->toDateString();

                $query->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', $today)
                    ->whereDate('expiry_date', '<=', $until);
            })
            ->orderBy('goods_donation_id')
            ->orderByDesc('created_at')
            ->get();

        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'goods_donation_id' => $item->goods_donation_id,
                'name' => $item->name,
                'image' => $item->image,
                'category' => $item->category,
                'category_name' => optional($item->categoryModel)->name,
                'sub_category' => $item->sub_category,
                'sub_category_name' => optional($item->subCategoryModel)->name,
                'quantity' => $item->quantity,
                'status' => $item->quantity > 0 ? 'available' : 'consumed',
                'unit' => $item->unit,
                'notes' => $item->notes,
                'expiry_date' => $item->expiry_date,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });

    }

    public function getConfirmedItems(array $filters = [])
    {
        $items = Item::with([
            'categoryModel:id,name',
            'subCategoryModel:id,name'
        ])
            ->where('is_confirmed', true)
            ->when(isset($filters['start_date']) && $filters['start_date'] !== '', function ($query) use ($filters) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            })
            ->when(isset($filters['end_date']) && $filters['end_date'] !== '', function ($query) use ($filters) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            })
            ->when(isset($filters['search']) && $filters['search'] !== '', function ($query) use ($filters) {
                $search = $filters['search'];
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('categoryModel', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('subCategoryModel', function ($subcategoryQuery) use ($search) {
                            $subcategoryQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when(isset($filters['near_expiration_days']) && (int) $filters['near_expiration_days'] > 0, function ($query) use ($filters) {
                $days = (int) $filters['near_expiration_days'];
                $today = now()->toDateString();
                $until = now()->addDays($days)->toDateString();

                $query->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', $today)
                    ->whereDate('expiry_date', '<=', $until);
            })
            ->orderBy('goods_donation_id')
            ->orderByDesc('created_at')
            ->get();

        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'goods_donation_id' => $item->goods_donation_id,
                'name' => $item->name,
                'image' => $item->image,
                'category' => $item->category,
                'category_name' => optional($item->categoryModel)->name,
                'sub_category' => $item->sub_category,
                'sub_category_name' => optional($item->subCategoryModel)->name,
                'quantity' => $item->quantity,
                'status' => $item->quantity > 0 ? 'available' : 'consumed',
                'unit' => $item->unit,
                'notes' => $item->notes,
                'expiry_date' => $item->expiry_date,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ];
        });
    }


    /**
     * Service function for retrieving items by id
     *
     * @param int
     * @return mixed
     */
    public function getItemsByDonationId(int $id)
    {
        return Item::where('goods_donation_id', $id)->latest()->get();
    }

    public function getItemsById(int $id)
    {
        return Item::with([
            'goodsDonation:id,name,email,address,status',
            'categoryModel:id,name',
            'subCategoryModel:id,name',
        ])->findOrFail($id);
    }

    /**
     * Service function for saving items
     *
     * @param int // id of the model to add the item/s from
     * @param array
     * @return mixed
     */
    public function saveItems(int $id, array $data)
    {
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
    public function updateItem(int $id, array $data)
    {
        Item::findOrFail($id)->update($data);
    }

    /**
     * Service function for deleting items by id
     *
     * @param int
     * @return mixed
     */
    public function deleteItem(int $id)
    {
        $item = Item::findOrFail($id);
        $item->delete();

    }
}
