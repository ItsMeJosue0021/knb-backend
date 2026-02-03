<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageInfo;
use App\Models\CarouselImage;
use App\Models\Programs;
use App\Models\Encouragement;
use App\Models\Quotes;
use App\Models\Involvement;
use Illuminate\Support\Facades\Storage;

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

    public function getCarouselImages()
    {
        $images = CarouselImage::orderBy('created_at', 'desc')->get();

        return response()->json($images, 200);
    }

    public function saveCarouselImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $saved = [];
        foreach ($request->file('images') as $imageFile) {
            $path = $imageFile->store('carousel_images', 'public');
            $saved[] = CarouselImage::create(['image_path' => $path]);
        }

        return response()->json([
            'message' => 'Carousel images saved successfully',
            'data' => $saved,
        ], 201);
    }

    public function deleteCarouselImage($id)
    {
        $image = CarouselImage::findOrFail($id);

        if ($image->image_path) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json([
            'message' => 'Carousel image deleted successfully',
        ], 200);
    }

    public function savePrograms(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'programs' => 'nullable|array',
            'programs.*.id' => 'nullable|integer',
            'programs.*.title' => 'required_with:programs|string',
            'programs.*.description' => 'required_with:programs|string',
        ]);

        $programs = Programs::first();
        if ($programs) {
            $programs->update($validated);
        } else {
            $programs = Programs::create($validated);
        }

        return response()->json([
            'message' => 'Programs saved successfully',
            'data' => $programs,
        ], 200);
    }

    public function getProgramsInfo()
    {
        $programs = Programs::first();

        return response()->json($programs, 200);
    }

    public function saveEncouragement(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'checklist' => 'nullable|array',
            'checklist.*.item' => 'required_with:checklist|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $encouragement = Encouragement::first();
        if ($encouragement && $request->hasFile('image') && $encouragement->image_path) {
            Storage::disk('public')->delete($encouragement->image_path);
        }

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('encouragement', 'public');
        }

        if ($encouragement) {
            $encouragement->update($validated);
        } else {
            $encouragement = Encouragement::create($validated);
        }

        return response()->json([
            'message' => 'Encouragement saved successfully',
            'data' => $encouragement,
        ], 200);
    }

    public function getEncouragementInfo()
    {
        $encouragement = Encouragement::first();

        return response()->json($encouragement, 200);
    }

    public function saveQuotes(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'quotes' => 'nullable|array',
            'quotes.*.quote' => 'required_with:quotes|string',
            'quotes.*.author' => 'required_with:quotes|string',
        ]);

        $quotes = Quotes::first();
        if ($quotes) {
            $quotes->update($validated);
        } else {
            $quotes = Quotes::create($validated);
        }

        return response()->json([
            'message' => 'Quotes saved successfully',
            'data' => $quotes,
        ], 200);
    }

    public function getQuotesInfo()
    {
        $quotes = Quotes::first();

        return response()->json($quotes, 200);
    }

    public function saveInvolvement(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'involvements' => 'required|array|size:3',
            'involvements.*.id' => 'nullable|integer',
            'involvements.*.title' => 'required_with:involvements|string',
            'involvements.*.description' => 'required_with:involvements|string',
            'involvements.*.url' => 'required_with:involvements|string',
        ]);

        $involvement = Involvement::first();
        if ($involvement) {
            $involvement->update($validated);
        } else {
            $involvement = Involvement::create($validated);
        }

        return response()->json([
            'message' => 'Involvement saved successfully',
            'data' => $involvement,
        ], 200);
    }

    public function getInvolvementInfo()
    {
        $involvement = Involvement::first();

        return response()->json($involvement, 200);
    }

}
