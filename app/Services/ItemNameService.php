<?php

namespace App\Services;

use App\Models\ItemName;
use Illuminate\Support\Collection;

class ItemNameService
{
    public function saveNewName(array $data): ItemName
    {
        return ItemName::create($data);
    }

    public function getAllNames(): Collection
    {
        return ItemName::orderBy('name')->get(['id', 'name']);
    }

    public function updateName(int $id, array $data): ItemName
    {
        $itemName = ItemName::findOrFail($id);
        $itemName->update($data);

        return $itemName->fresh();
    }

    public function deleteName(int $id): void
    {
        $itemName = ItemName::findOrFail($id);
        $itemName->delete();
    }

    public function getSuggestions(string $query, int $limit = 10): Collection
    {
        $query = trim($query);

        if ($query === '') {
            return collect();
        }

        return ItemName::query()
            ->where('name', 'like', "%{$query}%")
            ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$query . '%'])
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name']);
    }
}
