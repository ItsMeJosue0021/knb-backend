<?php

namespace App\Services;

use App\Models\GoodsDonation;
use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
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
        $inventoryItem = $this->resolveInventoryItemBySourceItem($sourceItem);

        if ($inventoryItem->quantity < $quantity) {
            throw new RuntimeException("Insufficient general inventory quantity for {$sourceItem->name}.");
        }

        $inventoryItem->decrement('quantity', $quantity);

        InventoryTransaction::create(array_merge($this->inventoryTransactionSnapshot($inventoryItem), [
            'inventory_item_id' => $inventoryItem->id,
            'project_id' => $projectId,
            'source_item_id' => $sourceItem->id,
            'type' => 'out',
            'quantity' => $quantity,
            'occurred_at' => now(),
            'source_name' => $sourceItem->name,
            'unit' => $this->normalizeUnit($sourceItem->unit),
            'notes' => "Used for project #{$projectId}",
        ]));
    }

    public function reconcileInventoryHistoryItems(bool $dryRun = true): array
    {
        $summary = [
            'mode' => $dryRun ? 'dry_run' : 'commit',
            'scanned_inventory_items' => 0,
            'split_candidates' => 0,
            'inventory_items_created' => 0,
            'inventory_items_updated' => 0,
            'transactions_to_reassign' => 0,
            'transactions_reassigned' => 0,
            'inventory_items_recalculated' => 0,
            'items' => [],
        ];

        if ($dryRun) {
            $this->simulateReconcileInventoryHistoryItems($summary);
            return $summary;
        }

        DB::transaction(function () use (&$summary) {
            InventoryItem::query()
                ->whereHas('transactions')
                ->with([
                    'transactions' => function ($query) {
                        $query->with('sourceItem:id,name');
                    },
                ])
                ->orderBy('id')
                ->chunkById(200, function ($items) use (&$summary) {
                    foreach ($items as $item) {
                        $summary['scanned_inventory_items']++;

                        $plan = $this->buildReconcilePlanForInventoryItem($item);
                        if (!empty($plan['groups']) && count($plan['groups']) > 1) {
                            $summary['split_candidates']++;
                        }

                        $hasSingleGroup = empty($plan['groups']) || count($plan['groups']) <= 1;
                        if ($hasSingleGroup) {
                            if (!empty($plan['single_name']) && $item->item_name !== $plan['single_name']) {
                                $item->update(['item_name' => $plan['single_name']]);
                                $summary['inventory_items_updated']++;
                            }
                            continue;
                        }

                        $this->applyReconcilePlan($item, $plan, $summary);
                    }
                });
        });

        return $summary;
    }

    private function simulateReconcileInventoryHistoryItems(array &$summary): void
    {
        InventoryItem::query()
            ->whereHas('transactions')
            ->with([
                'transactions' => function ($query) {
                    $query->with('sourceItem:id,name');
                },
            ])
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$summary) {
                foreach ($items as $item) {
                    $summary['scanned_inventory_items']++;

                    $plan = $this->buildReconcilePlanForInventoryItem($item);
                    if (!empty($plan['groups']) && count($plan['groups']) > 1) {
                        $summary['split_candidates']++;
                    }

                    if (empty($plan['groups']) || count($plan['groups']) <= 1) {
                        if (!empty($plan['single_name']) && $item->item_name !== $plan['single_name']) {
                            $summary['inventory_items_updated']++;
                        }
                        continue;
                    }

                    $summary['inventory_items_updated']++;
                    foreach ($plan['groups'] as $group) {
                        if ($group['target_inventory_item_id'] === null) {
                            $summary['inventory_items_created']++;
                        }

                        $summary['transactions_to_reassign'] += $group['transaction_count'];
                    }
                }
            });
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

        return DB::transaction(function () use ($sourceItem, $goodsDonationId) {
            $inventoryItem = $this->resolveInventoryItemBySourceItem($sourceItem);

            $inventoryItem->increment('quantity', (int) $sourceItem->quantity);

            InventoryTransaction::create(array_merge($this->inventoryTransactionSnapshot($inventoryItem), [
                'inventory_item_id' => $inventoryItem->id,
                'goods_donation_id' => $goodsDonationId ?? $sourceItem->goods_donation_id,
                'source_item_id' => $sourceItem->id,
                'type' => 'in',
                'quantity' => (int) $sourceItem->quantity,
                'occurred_at' => now(),
                'source_name' => $sourceItem->name,
                'unit' => $this->normalizeUnit($sourceItem->unit),
                'notes' => $sourceItem->notes,
            ]));

            return true;
        });
    }

    private function inventoryTransactionSnapshot(InventoryItem $inventoryItem): array
    {
        return [
            'snapshot_name' => $inventoryItem->item_name,
            'snapshot_category_id' => (int) $inventoryItem->category_id,
            'snapshot_sub_category_id' => (int) $inventoryItem->sub_category_id,
            'snapshot_unit' => $this->normalizeUnit($inventoryItem->unit),
        ];
    }

    private function normalizeUnit(?string $unit): string
    {
        return trim((string) $unit);
    }

    private function normalizeItemName(?string $name): string
    {
        return trim((string) $name);
    }

    private function resolveInventoryItemBySourceItem(Item $sourceItem): InventoryItem
    {
        $categoryId = (int) $sourceItem->category;
        $subCategoryId = (int) $sourceItem->sub_category;
        $unit = $this->normalizeUnit($sourceItem->unit);
        $itemName = $this->normalizeItemName($sourceItem->name);
        return InventoryItem::query()
            ->lockForUpdate()
            ->firstOrCreate(
                [
                    'category_id' => $categoryId,
                    'sub_category_id' => $subCategoryId,
                    'item_name' => $itemName,
                    'unit' => $unit,
                ],
                [
                    'quantity' => 0,
                ]
            );
    }

    private function buildReconcilePlanForInventoryItem(InventoryItem $item): array
    {
        $groups = [];
        $singleName = null;

        $transactions = $item->transactions()->with('sourceItem:id,name')->get();

        foreach ($transactions as $transaction) {
            $resolvedName = $this->normalizeItemName($this->resolveTransactionItemName($transaction));
            $unit = $this->normalizeUnit($transaction->unit ?: $item->unit);
            $key = "{$resolvedName}|{$unit}";

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'item_name' => $resolvedName,
                    'unit' => $unit,
                    'transaction_ids' => [],
                ];
            }

            $groups[$key]['transaction_ids'][] = $transaction->id;
        }

        if (count($groups) === 1 && !empty($groups)) {
            $single = reset($groups);
            $singleName = $single['item_name'];
            $single['target_inventory_item_id'] = $item->id;
            $groups = [$single];
        } elseif (count($groups) > 1) {
            foreach ($groups as $key => $group) {
                $groups[$key]['target_inventory_item_id'] = $this->findOrPlanTargetInventoryItemId($item, $group);
                $groups[$key]['transaction_count'] = count($group['transaction_ids']);
            }
        }

        return [
            'groups' => $groups,
            'single_name' => $singleName,
        ];
    }

    private function applyReconcilePlan(InventoryItem $item, array $plan, array &$summary): void
    {
        $resolvedTargets = [];

        foreach ($plan['groups'] as $group) {
            $targetItem = InventoryItem::query()
                ->lockForUpdate()
                ->where([
                    'category_id' => $item->category_id,
                    'sub_category_id' => $item->sub_category_id,
                    'item_name' => $group['item_name'],
                    'unit' => $group['unit'],
                ])
                ->first();

            if (!$targetItem) {
                $targetItem = InventoryItem::query()->create([
                    'category_id' => $item->category_id,
                    'sub_category_id' => $item->sub_category_id,
                    'item_name' => $group['item_name'],
                    'unit' => $group['unit'],
                    'quantity' => 0,
                ]);
                $summary['inventory_items_created']++;
            }

            if ($targetItem->id === $item->id && $targetItem->item_name !== $group['item_name']) {
                $targetItem->update(['item_name' => $group['item_name']]);
                $summary['inventory_items_updated']++;
            }

            $resolvedTargets[] = $targetItem->id;

            $groupTransactionIds = $group['transaction_ids'];
            $reassignToTarget = array_diff($groupTransactionIds, InventoryTransaction::query()
                ->where('inventory_item_id', $targetItem->id)
                ->whereIn('id', $groupTransactionIds)
                ->pluck('id')
                ->all());

            if (!empty($reassignToTarget)) {
                InventoryTransaction::query()
                    ->whereIn('id', $reassignToTarget)
                    ->update(['inventory_item_id' => $targetItem->id]);

                $summary['transactions_reassigned'] += count($reassignToTarget);
            }
        }

        $affectedInventoryItems = array_unique(array_merge([$item->id], $resolvedTargets));

        foreach ($affectedInventoryItems as $inventoryItemId) {
            $this->refreshInventoryItemQuantity($inventoryItemId);
            $summary['inventory_items_recalculated']++;
        }
    }

    private function findOrPlanTargetInventoryItemId(InventoryItem $sourceItem, array $group): ?int
    {
        $target = InventoryItem::query()
            ->where([
                'category_id' => $sourceItem->category_id,
                'sub_category_id' => $sourceItem->sub_category_id,
                'item_name' => $group['item_name'],
                'unit' => $group['unit'],
            ])
            ->first();

        return $target?->id;
    }

    private function refreshInventoryItemQuantity(int $inventoryItemId): void
    {
        $balance = (int) InventoryTransaction::query()
            ->where('inventory_item_id', $inventoryItemId)
            ->sum(DB::raw("CASE WHEN `type` = 'in' THEN `quantity` WHEN `type` = 'out' THEN -`quantity` ELSE 0 END"));

        InventoryItem::query()
            ->where('id', $inventoryItemId)
            ->update(['quantity' => $balance]);
    }

    private function resolveTransactionItemName(InventoryTransaction $transaction): string
    {
        $sourceName = trim((string) $transaction->source_name);
        if ($sourceName !== '') {
            return $sourceName;
        }

        $itemName = trim((string) optional($transaction->sourceItem)->name);
        if ($itemName !== '') {
            return $itemName;
        }

        $inventoryItemName = trim((string) optional($transaction->inventoryItem)->item_name);
        if ($inventoryItemName !== '') {
            return $inventoryItemName;
        }

        return 'Unknown item';
    }
}
