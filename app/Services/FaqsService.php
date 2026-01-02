<?php

namespace App\Services;

use App\Models\Faqs;

class FaqsService
{
    /**
     * Retrieve FAQs with optional search across question, answer, and category.
     *
     * @param string|null $searchTerm
     * @return \Illuminate\Database\Eloquent\Collection<int, Faqs>
     */
    public function getAll(?string $searchTerm = null)
    {
        return Faqs::when($searchTerm, function ($query, $searchTerm) {
                $like = '%' . $searchTerm . '%';

                $query->where(function ($subQuery) use ($like) {
                    $subQuery->where('question', 'like', $like)
                        ->orWhere('answer', 'like', $like)
                        ->orWhere('category', 'like', $like);
                });
            })
            ->latest()
            ->get();
    }

    /**
     * Create a new FAQ entry.
     */
    public function store(array $data): Faqs
    {
        return Faqs::create($data);
    }

    /**
     * Update an existing FAQ.
     */
    public function update(int $id, array $data): Faqs
    {
        $faq = Faqs::findOrFail($id);
        $faq->update($data);

        return $faq;
    }

    /**
     * Delete an FAQ by id.
     */
    public function delete(int $id): void
    {
        $faq = Faqs::findOrFail($id);
        $faq->delete();
    }
}
