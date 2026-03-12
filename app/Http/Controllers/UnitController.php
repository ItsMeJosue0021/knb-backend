<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index()
    {
        return response([
            'units' => Unit::query()->orderBy('id')->get(),
        ], 200);
    }

    public function show(int $id)
    {
        return response([
            'unit' => Unit::query()->findOrFail($id),
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'unit' => ['required', 'string', 'max:50', 'unique:units,unit'],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $unit = Unit::create([
            'unit' => trim($validated['unit']),
            'description' => trim($validated['description']),
        ]);

        return response([
            'message' => 'Unit created successfully.',
            'unit' => $unit,
        ], 201);
    }

    public function update(Request $request, int $id)
    {
        $unit = Unit::query()->findOrFail($id);

        $validated = $request->validate([
            'unit' => ['required', 'string', 'max:50', Rule::unique('units', 'unit')->ignore($unit->id)],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $unit->update([
            'unit' => trim($validated['unit']),
            'description' => trim($validated['description']),
        ]);

        return response([
            'message' => 'Unit updated successfully.',
            'unit' => $unit->fresh(),
        ], 200);
    }

    public function destroy(int $id)
    {
        $unit = Unit::query()->findOrFail($id);
        $unit->delete();

        return response([
            'message' => 'Unit deleted successfully.',
        ], 200);
    }
}
