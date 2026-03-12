<?php

namespace App\Http\Controllers;

use App\Models\GDSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GDSubcategoryController extends Controller
{
    public function index()
    {
        return response([
            'subcategories' => GDSubcategory::query()
                ->with('category')
                ->orderBy('name')
                ->get()
        ], 200);
    }

    public function show(int $id)
    {
        return response([
            'subcategory' => GDSubcategory::query()
                ->with('category')
                ->findOrFail($id),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'g_d_category_id' => ['required', 'integer', 'exists:g_d_categories,id'],
            'name' => ['required', 'string', 'max:255', 'unique:g_d_subcategories,name'],
        ]);

        $subcategory = GDSubcategory::query()->create([
            'g_d_category_id' => $validated['g_d_category_id'],
            'name' => trim($validated['name']),
        ]);

        return response([
            'message' => 'Subcategory created successfully.',
            'subcategory' => $subcategory->fresh('category'),
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $subcategory = GDSubcategory::query()->findOrFail($id);

        $validated = $request->validate([
            'g_d_category_id' => ['required', 'integer', 'exists:g_d_categories,id'],
            'name' => ['required', 'string', 'max:255', Rule::unique('g_d_subcategories', 'name')->ignore($subcategory->id)],
        ]);

        $subcategory->update([
            'g_d_category_id' => $validated['g_d_category_id'],
            'name' => trim($validated['name']),
        ]);

        return response([
            'message' => 'Subcategory updated successfully.',
            'subcategory' => $subcategory->fresh('category'),
        ], 200);
    }

    public function destroy(int $id)
    {
        $subcategory = GDSubcategory::query()->findOrFail($id);

        $hasDonationItems = DB::table('items')->where('sub_category', (string) $subcategory->id)->exists();
        $hasInventoryItems = DB::table('inventory_items')->where('sub_category_id', $subcategory->id)->exists();

        if ($hasDonationItems || $hasInventoryItems) {
            return response([
                'message' => 'This subcategory cannot be deleted because it is already used by donation items or inventory records.',
            ], 422);
        }

        $subcategory->delete();

        return response([
            'message' => 'Subcategory deleted successfully.',
        ], 200);
    }
}
