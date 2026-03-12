<?php

namespace App\Http\Controllers;

use App\Models\WebsiteLogo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WebsiteLogoController extends Controller
{
    public function show()
    {
        return response()->json(WebsiteLogo::first(), 200);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'main_text' => 'required|string|max:255',
            'secondary_text' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $websiteLogo = WebsiteLogo::first();

        if ($websiteLogo && $request->hasFile('image') && $websiteLogo->image_path) {
            Storage::disk('public')->delete($websiteLogo->image_path);
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('website-logo', 'public');
        }

        if ($websiteLogo) {
            $websiteLogo->update($validated);
        } else {
            $websiteLogo = WebsiteLogo::create($validated);
        }

        return response()->json([
            'message' => 'Website logo saved successfully',
            'data' => $websiteLogo,
        ], 200);
    }
}
