<?php

namespace App\Http\Controllers;

use App\Services\ItemNameService;
use Illuminate\Http\Request;

class ItemNameController extends Controller
{
    protected ItemNameService $itemNameService;

    public function __construct(ItemNameService $itemNameService)
    {
        $this->itemNameService = $itemNameService;
    }

    public function index()
    {
        return response()->json([
            'item_names' => $this->itemNameService->getAllNames(),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:item_names,name',
        ]);

        $itemName = $this->itemNameService->saveNewName($validated);

        return response()->json([
            'message' => 'Item name created successfully.',
            'data' => $itemName,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:item_names,name,' . $id,
        ]);

        $itemName = $this->itemNameService->updateName($id, $validated);

        return response()->json([
            'message' => 'Item name updated successfully.',
            'data' => $itemName,
        ], 200);
    }

    public function destroy(int $id)
    {
        $this->itemNameService->deleteName($id);

        return response()->json([
            'message' => 'Item name deleted successfully.',
        ], 200);
    }

    public function suggestions(Request $request)
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $suggestions = $this->itemNameService->getSuggestions(
            $validated['q'] ?? ($validated['search'] ?? ''),
            $validated['limit'] ?? 10
        );

        return response()->json([
            'suggestions' => $suggestions,
        ], 200);
    }
}
