<?php

namespace App\Http\Controllers;

use App\Models\GDCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GDCategoryController extends Controller
{
    public function index()
    {
        return response([
            'categories' => GDCategory::query()
                ->with(['subcategories' => function ($query) {
                    $query->orderBy('name');
                }])
                ->orderBy('name')
                ->get()
        ], 200);
    }

    public function show(int $id)
    {
        return response([
            'category' => GDCategory::query()
                ->with(['subcategories' => function ($query) {
                    $query->orderBy('name');
                }])
                ->findOrFail($id),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:g_d_categories,name'],
        ]);

        $category = GDCategory::query()->create([
            'name' => trim($validated['name']),
        ]);

        return response([
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $category = GDCategory::query()->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('g_d_categories', 'name')->ignore($category->id)],
        ]);

        $category->update([
            'name' => trim($validated['name']),
        ]);

        return response([
            'message' => 'Category updated successfully.',
            'category' => $category->fresh('subcategories'),
        ], 200);
    }

    public function destroy(int $id)
    {
        $category = GDCategory::query()->withCount('subcategories')->findOrFail($id);

        $hasDonationItems = DB::table('items')->where('category', (string) $category->id)->exists();
        $hasInventoryItems = DB::table('inventory_items')->where('category_id', $category->id)->exists();

        if ($hasDonationItems || $hasInventoryItems) {
            return response([
                'message' => 'This category cannot be deleted because it is already used by donation items or inventory records.',
            ], 422);
        }

        if ($category->subcategories_count > 0) {
            return response([
                'message' => 'Delete the subcategories under this category first before deleting it.',
            ], 422);
        }

        $category->delete();

        return response([
            'message' => 'Category deleted successfully.',
        ], 200);
    }
}
