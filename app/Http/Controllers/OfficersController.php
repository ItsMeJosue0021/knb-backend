<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Officers;

class OfficersController extends Controller
{
    /**
     * Retrieve all officers.
     */
    public function index()
    {
        $officers = Officers::latest()->get();

        return response()->json($officers, 200);
    }

    /**
     * Store a new officer.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'position' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $validated['photo_url'] = $request->file('image')->store('officers', 'public');
        }

        $officer = Officers::create($validated);

        return response()->json([
            'message' => 'Officer created successfully',
            'data' => $officer,
        ], 201);
    }

    /**
     * Update an officer by id.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'position' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $officer = Officers::findOrFail($id);

        if ($request->hasFile('image')) {
            $validated['photo_url'] = $request->file('image')->store('officers', 'public');
        }

        $officer->update($validated);

        return response()->json([
            'message' => 'Officer updated successfully',
            'data' => $officer,
        ], 200);
    }

    /**
     * Delete an officer by id.
     */
    public function destroy(int $id)
    {
        $officer = Officers::findOrFail($id);
        $officer->delete();

        return response()->json(null, 204);
    }
}
