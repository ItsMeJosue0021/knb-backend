<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use App\Services\InventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

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
        $validated = $this->expandInventoryItemFilterToSubcategory($validated);

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
        $validated = $this->expandInventoryItemFilterToSubcategory($validated);
        $validated['type'] = 'in';

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
        $inventoryItem = InventoryItem::find($inventoryItemId);

        $subcategoryId = $inventoryItem?->sub_category_id ?? $inventoryItemId;

        $subcategoryExists = InventoryItem::query()
            ->where('sub_category_id', $subcategoryId)
            ->exists();

        if (!$subcategoryExists) {
            return response()->json([
                'message' => 'Inventory item or subcategory not found.',
            ], 404);
        }

        $request->merge([
            'sub_category' => $subcategoryId,
        ]);

        return $this->history($request);
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
        return InventoryTransaction::query()
            ->with([
                'inventoryItem.categoryModel:id,name',
                'inventoryItem.subCategoryModel:id,name',
                'goodsDonation:id,name,email',
                'sourceItem:id,name,image,expiry_date,quantity,unit',
                'project:id,title,date',
            ])
            ->when(!empty($validated['inventory_item_id']), function ($query) use ($validated) {
                $query->where('inventory_item_id', $validated['inventory_item_id']);
            })
            ->when(!empty($validated['category']), function ($query) use ($validated) {
                $query->whereHas('inventoryItem', function ($sub) use ($validated) {
                    $sub->where('category_id', $validated['category']);
                });
            })
            ->when(!empty($validated['sub_category']), function ($query) use ($validated) {
                $query->whereHas('inventoryItem', function ($sub) use ($validated) {
                    $sub->where('sub_category_id', $validated['sub_category']);
                });
            })
            ->when(!empty($validated['type']), function ($query) use ($validated) {
                $query->where('type', $validated['type']);
            })
            ->when(!empty($validated['unit']), function ($query) use ($validated) {
                $query->where('unit', trim($validated['unit']));
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

    private function expandInventoryItemFilterToSubcategory(array $validated): array
    {
        if (empty($validated['inventory_item_id'])) {
            return $validated;
        }

        $inventoryItem = InventoryItem::find($validated['inventory_item_id']);

        if ($inventoryItem) {
            $validated['sub_category'] = (int) $inventoryItem->sub_category_id;
            unset($validated['inventory_item_id']);
            return $validated;
        }

        $subcategoryId = (int) $validated['inventory_item_id'];
        $subcategoryExists = InventoryItem::query()
            ->where('sub_category_id', $subcategoryId)
            ->exists();

        if ($subcategoryExists) {
            $validated['sub_category'] = $subcategoryId;
            unset($validated['inventory_item_id']);
        }

        return $validated;
    }

    private function getGroupedInventoryRows(array $validated)
    {
        $items = $this->buildInventoryQuery($validated)
            ->orderByDesc('quantity')
            ->orderBy('sub_category_id')
            ->get();

        return $items
            ->groupBy('sub_category_id')
            ->map(function ($group) {
                /** @var \App\Models\InventoryItem $first */
                $first = $group->first();

                $unitBreakdown = $group->map(function ($item) {
                    $unit = trim((string) $item->unit);

                    return [
                        'inventory_item_id' => $item->id,
                        'quantity' => (int) $item->quantity,
                        'unit' => $unit === '' ? '-' : $unit,
                    ];
                })->values();

                $hasMixedUnits = $unitBreakdown->pluck('unit')->unique()->count() > 1;

                return [
                    // Use subcategory as grouped row identifier.
                    'id' => $first->sub_category_id,
                    'category' => $first->category_id,
                    'category_name' => optional($first->categoryModel)->name,
                    'sub_category' => $first->sub_category_id,
                    'sub_category_name' => optional($first->subCategoryModel)->name,
                    // Display-only combined quantity for grouped rows.
                    'quantity' => (int) $group->sum('quantity'),
                    'unit' => $hasMixedUnits ? 'mixed' : ($unitBreakdown->first()['unit'] ?? '-'),
                    'has_mixed_units' => $hasMixedUnits,
                    'unit_breakdown' => $unitBreakdown,
                    'unit_breakdown_text' => $unitBreakdown
                        ->map(function ($entry) {
                            return $entry['quantity'] . ' ' . $entry['unit'];
                        })
                        ->implode(', '),
                    'inventory_item_ids' => $group->pluck('id')->values(),
                    'inventory_item_count' => $group->count(),
                    'created_at' => $group->min('created_at'),
                    'updated_at' => $group->max('updated_at'),
                ];
            })
            ->sortByDesc('quantity')
            ->values();
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
        return [
            'id' => $entry->id,
            'inventory_item_id' => $entry->inventory_item_id,
            'category' => optional($entry->inventoryItem)->category_id,
            'category_name' => optional(optional($entry->inventoryItem)->categoryModel)->name,
            'sub_category' => optional($entry->inventoryItem)->sub_category_id,
            'sub_category_name' => optional(optional($entry->inventoryItem)->subCategoryModel)->name,
            'type' => $entry->type,
            'quantity' => $entry->quantity,
            'unit' => $entry->unit,
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
}
