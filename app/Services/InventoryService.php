<?php

namespace App\Services;

use App\Models\GoodsDonation;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Item;
use RuntimeException;

class InventoryService
{
    public function syncApprovedDonation(GoodsDonation $donation): int
    {
        $donation->loadMissing('items');

        $synced = 0;
        foreach ($donation->items as $item) {
            if ($this->recordIncomingItem($item, $donation->id)) {
                $synced++;
            }
        }

        return $synced;
    }

    public function syncAllConfirmedItems(): int
    {
        $items = Item::query()
            ->where('is_confirmed', true)
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('inventory_transactions')
                    ->whereColumn('inventory_transactions.source_item_id', 'items.id')
                    ->where('inventory_transactions.type', 'in');
            })
            ->orderBy('id')
            ->get();

        $synced = 0;
        foreach ($items as $item) {
            if ($this->recordIncomingItem($item, $item->goods_donation_id)) {
                $synced++;
            }
        }

        return $synced;
    }

    public function consumeFromProject(Item $sourceItem, int $quantity, int $projectId): void
    {
        $inventoryItem = InventoryItem::query()
            ->lockForUpdate()
            ->firstOrCreate(
                [
                    'sub_category_id' => (int) $sourceItem->sub_category,
                    'unit' => $this->normalizeUnit($sourceItem->unit),
                ],
                [
                    'category_id' => (int) $sourceItem->category,
                    'quantity' => 0,
                ]
            );

        if ($inventoryItem->quantity < $quantity) {
            throw new RuntimeException("Insufficient general inventory quantity for {$sourceItem->name}.");
        }

        $inventoryItem->decrement('quantity', $quantity);

        InventoryTransaction::create([
            'inventory_item_id' => $inventoryItem->id,
            'project_id' => $projectId,
            'source_item_id' => $sourceItem->id,
            'type' => 'out',
            'quantity' => $quantity,
            'occurred_at' => now(),
            'source_name' => $sourceItem->name,
            'unit' => $this->normalizeUnit($sourceItem->unit),
            'notes' => "Used for project #{$projectId}",
        ]);
    }

    private function recordIncomingItem(Item $sourceItem, ?int $goodsDonationId = null): bool
    {
        if (!$sourceItem->is_confirmed || $sourceItem->quantity <= 0) {
            return false;
        }

        $alreadySynced = InventoryTransaction::query()
            ->where('type', 'in')
            ->where('source_item_id', $sourceItem->id)
            ->exists();

        if ($alreadySynced) {
            return false;
        }

        $inventoryItem = InventoryItem::query()
            ->lockForUpdate()
            ->firstOrCreate(
                [
                    'sub_category_id' => (int) $sourceItem->sub_category,
                    'unit' => $this->normalizeUnit($sourceItem->unit),
                ],
                [
                    'category_id' => (int) $sourceItem->category,
                    'quantity' => 0,
                ]
            );

        if ((int) $inventoryItem->category_id !== (int) $sourceItem->category) {
            $inventoryItem->update([
                'category_id' => (int) $sourceItem->category,
            ]);
        }

        $inventoryItem->increment('quantity', (int) $sourceItem->quantity);

        InventoryTransaction::create([
            'inventory_item_id' => $inventoryItem->id,
            'goods_donation_id' => $goodsDonationId ?? $sourceItem->goods_donation_id,
            'source_item_id' => $sourceItem->id,
            'type' => 'in',
            'quantity' => (int) $sourceItem->quantity,
            'occurred_at' => now(),
            'source_name' => $sourceItem->name,
            'unit' => $this->normalizeUnit($sourceItem->unit),
            'notes' => $sourceItem->notes,
        ]);

        return true;
    }

    private function normalizeUnit(?string $unit): string
    {
        return trim((string) $unit);
    }
}

