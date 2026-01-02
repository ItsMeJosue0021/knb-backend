<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FaqsService;

class FaqsController extends Controller
{
    protected $faqsService;

    public function __construct(FaqsService $faqsService)
    {
        $this->faqsService = $faqsService;
    }

    /**
     * Get all FAQs with optional search.
     */
    public function index(Request $request)
    {
        $faqs = $this->faqsService->getAll($request->input('search'));

        return response()->json($faqs, 200);
    }

    /**
     * Create a new FAQ entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'required|in:general,donation',
        ]);

        $faq = $this->faqsService->store($validated);

        return response()->json([
            'message' => 'FAQ created successfully',
            'data' => $faq,
        ], 201);
    }

    /**
     * Update an FAQ entry.
     */
    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'category' => 'required|in:general,donation',
        ]);

        $faq = $this->faqsService->update($id, $validated);

        return response()->json([
            'message' => 'FAQ updated successfully',
            'data' => $faq,
        ], 200);
    }

    /**
     * Delete an FAQ entry.
     */
    public function destroy(int $id)
    {
        $this->faqsService->delete($id);

        return response()->json(null, 204);
    }
}
