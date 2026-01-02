<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageInfo;

class HomepageInfoController extends Controller
{
    /**
     * Retrieve the single HomepageInfo record.
     */
    public function show()
    {
        $info = HomepageInfo::first();

        return response()->json($info, 200);
    }

    /**
     * Create or update the HomepageInfo record (singleton).
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'welcome_message' => 'required|string',
            'intro_text' => 'required|string',
            'women_supported' => 'nullable|string',
            'meals_served' => 'nullable|string',
            'communities_reached' => 'nullable|string',
            'number_of_volunteers' => 'nullable|string',
        ]);

        $info = HomepageInfo::first();

        if ($info) {
            $info->update($validated);
        } else {
            $info = HomepageInfo::create($validated);
        }

        return response()->json([
            'message' => 'Homepage info saved successfully',
            'data' => $info,
        ], 200);
    }
}
