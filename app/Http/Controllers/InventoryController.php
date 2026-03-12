<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;

    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $validated = $this->validateInventoryFilters($request);

        $items = $this->getGroupedInventoryRows($validated);

        return response()->json([
            'items' => $items,
        ], 200);
    }

    public function history(Request $request)
    {
        $validated = $this->validateHistoryFilters($request);

        $history = $this->buildInventoryHistoryQuery($validated)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($entry) {
                return $this->mapInventoryHistoryEntry($entry);
            });

        return response()->json([
            'history' => $history,
        ], 200);
    }

    public function printInventory(Request $request)
    {
        $validated = $this->validateInventoryFilters($request);

        $items = $this->getGroupedInventoryRows($validated);

        $pdf = Pdf::loadView('inventory.inventory-report', [
            'items' => $items,
            'generatedAt' => now(),
            'filters' => $validated,
        ])->setPaper('a4', 'portrait');

        return $pdf->stream('inventory-report.pdf');
    }

    public function printHistory(Request $request)
    {
        $validated = $this->validateHistoryFilters($request);

        $history = $this->buildInventoryHistoryQuery($validated)
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get()
            ->map(function ($entry) {
                return $this->mapInventoryHistoryEntry($entry);
            });

        $pdf = Pdf::loadView('inventory.history-report', [
            'history' => $history,
            'generatedAt' => now(),
            'filters' => $validated,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('inventory-history-report.pdf');
    }

    public function historyBySubcategory(Request $request, int $subcategoryId)
    {
        $request->merge([
            'sub_category' => $subcategoryId,
        ]);

        return $this->history($request);
    }

    public function historyByInventoryItem(Request $request, int $inventoryItemId)
    {
        $forceItemHistory = filter_var($request->query('force_item', false), FILTER_VALIDATE_BOOLEAN);

        $inventoryItem = InventoryItem::find($inventoryItemId);

        if ($inventoryItem) {
            $request->merge([
                'inventory_item_id' => $inventoryItem->id,
            ]);

            return $this->history($request);
        }

        $subcategoryExists = InventoryItem::query()
            ->where('sub_category_id', $inventoryItemId)
            ->exists();

        if (!$forceItemHistory && $subcategoryExists) {
            return $this->historyBySubcategory($request, $inventoryItemId);
        }

        if (!$subcategoryExists) {
            return response()->json([
                'message' => 'Inventory item or subcategory not found.',
            ], 404);
        }

        return $this->historyBySubcategory($request, $inventoryItemId);
    }

    public function inHistory(Request $request)
    {
        $request->merge([
            'type' => 'in',
        ]);

        return $this->history($request);
    }

    public function outHistory(Request $request)
    {
        $request->merge([
            'type' => 'out',
        ]);

        return $this->history($request);
    }

    public function syncConfirmedItems()
    {
        $synced = $this->inventoryService->syncAllConfirmedItems();

        return response()->json([
            'message' => 'Inventory synced with confirmed donation items.',
            'synced_items' => $synced,
        ], 200);
    }

    public function reconcileHistoryItems(Request $request)
    {
        $validated = $request->validate([
            'dry_run' => 'nullable|boolean',
            'confirm' => 'nullable|boolean',
        ]);

        $dryRun = filter_var($validated['dry_run'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if (!$dryRun && !filter_var($validated['confirm'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Confirmation required for non-dry-run reconcile.',
                'hint' => 'Pass confirm=true to persist changes.',
            ], 400);
        }

        $result = $this->inventoryService->reconcileInventoryHistoryItems($dryRun);

        return response()->json([
            'status' => 'success',
            'result' => $result,
        ], 200);
    }

    private function validateInventoryFilters(Request $request): array
    {
        return $request->validate([
            'search' => 'nullable|string|max:100',
            'category' => 'nullable|integer|exists:g_d_categories,id',
            'sub_category' => 'nullable|integer|exists:g_d_subcategories,id',
            'include_zero' => 'nullable|boolean',
            'near_expiration_days' => 'nullable|integer|min:1|max:365',
        ]);
    }

    private function validateHistoryFilters(Request $request): array
    {
        return $request->validate([
            'inventory_item_id' => 'nullable|integer',
            'item_name' => 'nullable|string|max:255',
            'category' => 'nullable|integer|exists:g_d_categories,id',
            'sub_category' => 'nullable|integer|exists:g_d_subcategories,id',
            'unit' => 'nullable|string|max:50',
            'type' => 'nullable|in:in,out,adjustment',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'near_expiration_days' => 'nullable|integer|min:1|max:365',
        ]);
    }

    private function buildInventoryQuery(array $validated)
    {
        $includeZero = filter_var($validated['include_zero'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return InventoryItem::query()
            ->with([
                'categoryModel:id,name',
                'subCategoryModel:id,name',
            ])
            ->when(!$includeZero, function ($query) {
                $query->where('quantity', '>', 0);
            })
            ->when(!empty($validated['category']), function ($query) use ($validated) {
                $query->where('category_id', $validated['category']);
            })
            ->when(!empty($validated['sub_category']), function ($query) use ($validated) {
                $query->where('sub_category_id', $validated['sub_category']);
            })
            ->when(!empty($validated['search']), function ($query) use ($validated) {
                $search = $validated['search'];
                $query->where(function ($sub) use ($search) {
                    $sub->whereHas('categoryModel', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', "%{$search}%");
                    })->orWhereHas('subCategoryModel', function ($subcategoryQuery) use ($search) {
                        $subcategoryQuery->where('name', 'like', "%{$search}%");
                    });
                });
            })
            ->when(!empty($validated['near_expiration_days']), function ($query) use ($validated) {
                $days = (int) $validated['near_expiration_days'];
                $today = now()->toDateString();
                $until = now()->addDays($days)->toDateString();

                $query->whereHas('transactions', function ($transactionQuery) use ($today, $until) {
                    $transactionQuery->where('type', 'in')
                        ->whereHas('sourceItem', function ($sourceItemQuery) use ($today, $until) {
                            $sourceItemQuery->whereNotNull('expiry_date')
                                ->whereDate('expiry_date', '>=', $today)
                                ->whereDate('expiry_date', '<=', $until)
                                ->where('quantity', '>', 0);
                        });
                });
            });
    }

    private function buildInventoryHistoryQuery(array $validated)
    {
        $hasInventoryItemName = Schema::hasColumn('inventory_items', 'item_name');
        $hasInventoryTransactionSnapshotName = Schema::hasColumn('inventory_transactions', 'snapshot_name');
        $hasInventoryTransactionSnapshotCategory = Schema::hasColumn('inventory_transactions', 'snapshot_category_id');
        $hasInventoryTransactionSnapshotSubCategory = Schema::hasColumn('inventory_transactions', 'snapshot_sub_category_id');

        return InventoryTransaction::query()
            ->with([
                'inventoryItem.categoryModel:id,name',
                'inventoryItem.subCategoryModel:id,name',
                'snapshotCategory:id,name',
                'snapshotSubCategory:id,name',
                'goodsDonation:id,name,email',
                'sourceItem:id,name,image,expiry_date,quantity,unit',
                'project:id,title,date',
            ])
            ->when(!empty($validated['inventory_item_id']), function ($query) use ($validated) {
                $query->where('inventory_item_id', $validated['inventory_item_id']);
            })
            ->when(!empty($validated['category']), function ($query) use ($validated) {
                $query->where(function ($scope) use ($validated, $hasInventoryTransactionSnapshotCategory) {
                    if ($hasInventoryTransactionSnapshotCategory) {
                        $scope->where('snapshot_category_id', $validated['category']);
                    }

                    $scope->orWhereHas('inventoryItem', function ($sub) use ($validated) {
                        $sub->where('category_id', $validated['category']);
                    });
                });
            })
            ->when(!empty($validated['sub_category']), function ($query) use ($validated) {
                $query->where(function ($scope) use ($validated, $hasInventoryTransactionSnapshotSubCategory) {
                    if ($hasInventoryTransactionSnapshotSubCategory) {
                        $scope->where('snapshot_sub_category_id', $validated['sub_category']);
                    }

                    $scope->orWhereHas('inventoryItem', function ($sub) use ($validated) {
                        $sub->where('sub_category_id', $validated['sub_category']);
                    });
                });
            })
            ->when(!empty($validated['item_name']), function ($query) use ($validated, $hasInventoryItemName, $hasInventoryTransactionSnapshotName) {
                $itemName = trim((string) $validated['item_name']);

                $query->where(function ($nameQuery) use ($itemName, $hasInventoryItemName, $hasInventoryTransactionSnapshotName) {
                    if ($hasInventoryItemName) {
                        $nameQuery->whereHas('inventoryItem', function ($sub) use ($itemName) {
                            $sub->where('item_name', $itemName);
                        });
                    }

                    if ($hasInventoryTransactionSnapshotName) {
                        $nameQuery->orWhere('snapshot_name', $itemName);
                    }

                    $nameQuery->orWhere('source_name', $itemName)
                        ->orWhereHas('sourceItem', function ($sourceItemQuery) use ($itemName) {
                            $sourceItemQuery->where('name', $itemName);
                        });
                });
            })
            ->when(!empty($validated['type']), function ($query) use ($validated) {
                $query->where('type', $validated['type']);
            })
            ->when(!empty($validated['unit']), function ($query) use ($validated) {
                $query->where(function ($unitScope) use ($validated) {
                    $unitScope->where('unit', trim($validated['unit']));
                    $unitScope->orWhere('snapshot_unit', trim($validated['unit']));
                });
            })
            ->when(!empty($validated['start_date']), function ($query) use ($validated) {
                $query->whereDate('occurred_at', '>=', $validated['start_date']);
            })
            ->when(!empty($validated['end_date']), function ($query) use ($validated) {
                $query->whereDate('occurred_at', '<=', $validated['end_date']);
            })
            ->when(!empty($validated['near_expiration_days']), function ($query) use ($validated) {
                $days = (int) $validated['near_expiration_days'];
                $today = now()->toDateString();
                $until = now()->addDays($days)->toDateString();

                $query->whereHas('sourceItem', function ($sourceItemQuery) use ($today, $until) {
                    $sourceItemQuery->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>=', $today)
                        ->whereDate('expiry_date', '<=', $until)
                        ->where('quantity', '>', 0);
                });
            });
    }

    private function getGroupedInventoryRows(array $validated)
    {
        $items = $this->buildInventoryQuery($validated)
            ->orderByDesc('quantity')
            ->orderBy('sub_category_id')
            ->orderBy('id')
            ->get()
            ->map(function (InventoryItem $item) {
                $itemName = trim((string) $item->item_name);
                $resolvedName = $itemName !== '' ? $itemName : $this->resolveInventoryItemDisplayName($item);
                $unit = trim((string) $item->unit);
                $unitValue = $unit === '' ? '-' : $unit;

                return array_merge($this->mapInventoryItem($item), [
                    'inventory_item_name' => $resolvedName,
                    'item_name' => $resolvedName,
                    'unit' => $unitValue,
                    'has_mixed_units' => false,
                    'unit_breakdown' => [[
                        'inventory_item_id' => $item->id,
                        'quantity' => (int) $item->quantity,
                        'unit' => $unitValue,
                        'item_name' => $resolvedName,
                    ]],
                    'unit_breakdown_text' => ((int) $item->quantity) . ' ' . $unitValue,
                    'inventory_item_ids' => [$item->id],
                    'inventory_item_count' => 1,
                ]);
            })
            ->sort(function ($a, $b) {
                $quantityA = (int) ($a['quantity'] ?? 0);
                $quantityB = (int) ($b['quantity'] ?? 0);
                if ($quantityA !== $quantityB) {
                    return $quantityB <=> $quantityA;
                }

                $subcategoryA = (int) ($a['sub_category'] ?? 0);
                $subcategoryB = (int) ($b['sub_category'] ?? 0);
                if ($subcategoryA !== $subcategoryB) {
                    return $subcategoryA <=> $subcategoryB;
                }

                $nameA = strtolower(trim((string) ($a['inventory_item_name'] ?? $a['item_name'] ?? '')));
                $nameB = strtolower(trim((string) ($b['inventory_item_name'] ?? $b['item_name'] ?? '')));
                $nameCompare = $nameA <=> $nameB;
                if ($nameCompare !== 0) {
                    return $nameCompare;
                }

                return (int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0);
            })
            ->values();

        return $items;
    }

    private function mapInventoryItem(InventoryItem $item): array
    {
        return [
            'id' => $item->id,
            'category' => $item->category_id,
            'category_name' => optional($item->categoryModel)->name,
            'sub_category' => $item->sub_category_id,
            'sub_category_name' => optional($item->subCategoryModel)->name,
            'quantity' => $item->quantity,
            'unit' => $item->unit,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
        ];
    }

    private function mapInventoryHistoryEntry(InventoryTransaction $entry): array
    {
        $itemName = $this->resolveHistoryInventoryItemName($entry);

        return [
            'id' => $entry->id,
            'inventory_item_id' => $entry->inventory_item_id,
            'item_name' => $itemName,
            'inventory_item_name' => $itemName,
            'category' => $entry->snapshot_category_id ?? optional($entry->inventoryItem)->category_id,
            'category_name' => $entry->snapshot_category_id
                ? optional($entry->snapshotCategory)->name
                : optional(optional($entry->inventoryItem)->categoryModel)->name,
            'sub_category' => $entry->snapshot_sub_category_id ?? optional($entry->inventoryItem)->sub_category_id,
            'sub_category_name' => $entry->snapshot_sub_category_id
                ? optional($entry->snapshotSubCategory)->name
                : optional(optional($entry->inventoryItem)->subCategoryModel)->name,
            'type' => $entry->type,
            'quantity' => $entry->quantity,
            'unit' => $entry->snapshot_unit ?: $entry->unit,
            'occurred_at' => $entry->occurred_at,
            'goods_donation_id' => $entry->goods_donation_id,
            'goods_donation_name' => optional($entry->goodsDonation)->name,
            'source_item_id' => $entry->source_item_id,
            'source_item_name' => $entry->source_name ?: optional($entry->sourceItem)->name,
            'source_item_image' => optional($entry->sourceItem)->image,
            'source_item_expiry_date' => optional($entry->sourceItem)->expiry_date,
            'source_item_remaining_quantity' => optional($entry->sourceItem)->quantity,
            'source_item_unit' => optional($entry->sourceItem)->unit,
            'inventory_remaining_quantity' => optional($entry->inventoryItem)->quantity,
            'project_id' => $entry->project_id,
            'project_title' => optional($entry->project)->title,
            'notes' => $entry->notes,
            'created_at' => $entry->created_at,
            'updated_at' => $entry->updated_at,
        ];
    }

    private function resolveHistoryInventoryItemName(InventoryTransaction $entry): ?string
    {
        $snapshotName = trim((string) $entry->snapshot_name);
        if ($snapshotName !== '') {
            return $snapshotName;
        }

        $inventoryItemName = trim((string) optional($entry->inventoryItem)->item_name);
        if ($inventoryItemName !== '') {
            return $inventoryItemName;
        }

        $sourceName = trim((string) $entry->source_name);
        if ($sourceName !== '') {
            return $sourceName;
        }

        $sourceItemName = trim((string) optional($entry->sourceItem)->name);
        if ($sourceItemName !== '') {
            return $sourceItemName;
        }

        return 'Unknown item';
    }

    private function resolveInventoryItemDisplayName(InventoryItem $item): ?string
    {
        $itemName = trim((string) optional($item)->item_name);
        if ($itemName !== '') {
            return $itemName;
        }

        $sourceName = trim((string) InventoryTransaction::query()
            ->where('inventory_item_id', $item->id)
            ->whereNotNull('source_name')
            ->orderByDesc('id')
            ->value('source_name'));
        if ($sourceName !== '') {
            return $sourceName;
        }

        $sourceItemName = trim((string) InventoryTransaction::query()
            ->where('inventory_item_id', $item->id)
            ->whereNotNull('source_item_id')
            ->orderByDesc('id')
            ->with('sourceItem:id,name')
            ->first()?->sourceItem?->name);
        if ($sourceItemName !== '') {
            return $sourceItemName;
        }

        return 'Unknown item';
    }
}
