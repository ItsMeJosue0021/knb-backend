<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\GoodsDonation;
use App\Http\Requests\EditGDNameOrDescription;
use App\Services\GoodsDonationService;
use App\Services\InventoryService;
use App\Services\ItemService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\ContactInfo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoodsDonationController extends Controller
{

    protected GoodsDonationService $goodsDonationService;
    protected InventoryService $inventoryService;

    public function __construct(GoodsDonationService $goodsDonationService, InventoryService $inventoryService)
    {
        $this->goodsDonationService = $goodsDonationService;
        $this->inventoryService = $inventoryService;
    }

    public function index(Request $request)
    {
        $validated = $request->validate([
            'near_expiration_days' => 'nullable|integer|min:1|max:365',
        ]);

        $query = GoodsDonation::query();

        // if ($request->has('name')) {
        //     $query->where('name', 'like', '%' . $request->input('name') . '%');
        // }

        if ($request->has('name')) {
            $search = $request->input('name');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('address', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('month')) {
            $query->where('month', $request->input('month'));
        }

        if ($request->has('year')) {
            $query->where('year', $request->input('year'));
        }

        $nearExpirationDays = (int) ($validated['near_expiration_days'] ?? 0);

        if ($nearExpirationDays > 0) {
            $today = now()->toDateString();
            $until = now()->addDays($nearExpirationDays)->toDateString();

            $query->whereHas('items', function ($itemQuery) use ($today, $until) {
                $itemQuery->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', $today)
                    ->whereDate('expiry_date', '<=', $until);
            });
        }

        $donations = $query->latest()->get()->loadCount('items');

        return response()->json($donations);
    }
    public function store(Request $request, ItemService $itemService)
    {
        $validated = $request->validate([
            'type' => 'nullable|array',
            'description' => 'nullable|string',
            'quantity' => 'nullable|string',
            'address' => 'required|string',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'items' => 'nullable|array',
            'items.*.name' => 'required|string|max:255',
            'items.*.category' => 'required|integer|exists:g_d_categories,id',
            'items.*.sub_category' => 'required|integer|exists:g_d_subcategories,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.notes' => 'nullable|string',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.image' => 'nullable|image|mimes:jpeg,jpg,png,webp|max:2048',
        ]);

        $items = $validated['items'] ?? [];
        unset($validated['items']);

        $validated['type'] = $validated['type'] ?? [];
        $validated['description'] = $validated['description'] ?? '';
        $validated['year'] = now()->year;
        $validated['month'] = now()->format('F');

        $donation = GoodsDonation::create($validated);

        foreach ($items as $itemData) {
            $itemService->saveItems($donation->id, $itemData);
        }

        $types = implode(', ', $donation->type);

        $email = 'margeiremulta@gmail.com';

        // Email to admin
        $donorName = $donation->name ?? 'Someone';
        Mail::raw(
            "{$donorName} will be donating {$types} at your {$donation->address}.",
            function ($message) use ($email) {
                $message->to($email)->subject('New Goods Donation');
            }
        );

        // Email to donor
        if ($donation->email) {
            Mail::raw(
                'Please proceed to the chosen address to hand in your donations. Thank you so much, and may God bless you!',
                function ($message) use ($donation) {
                    $message->to($donation->email)->subject('Thank you for your donation!');
                }
            );
        }

        return response()->json([
            'message' => 'Donation successfully recorded.',
            'data' => $donation
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $donation = GoodsDonation::findOrFail($id);

        $validated = $request->validate([
            'type' => 'required|array',
            'description' => 'required|string',
            'quantity' => 'nullable|string',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $donation->update($validated);

        return response()->json([
            'message' => 'Donation updated successfully.',
            'data' => $donation
        ]);

    }

    public function updateNameOrDescription(EditGDNameOrDescription $request, $id)
    {
        $donation = $this->goodsDonationService->updateNameOrDescription($id, $request->validated());

        return response()->json([
            'message' => 'Donation updated successfully.',
            'data' => $donation
        ]);
    }

    public function show($id)
    {
        $donation = GoodsDonation::findOrFail($id);
        return response()->json($donation);
    }

    public function destroy($id)
    {
        $donation = GoodsDonation::findOrFail($id);
        $donation->delete();
        return response()->json(['message' => 'Donation deleted successfully.']);
    }


    /**
     * Retrieve all approved goods donations.
     */
    public function all()
    {
        $donations = GoodsDonation::orderBy('created_at', 'desc')
            ->get();

        return response()->json($donations);
    }

    /**
     * Filter goods donations by year, month, or both.
     * Example: /api/goods-donations/filter?year=2025&month=October&near_expiration_days=30
     */
    public function filter(Request $request)
    {
        $validated = $request->validate([
            'near_expiration_days' => 'nullable|integer|min:1|max:365',
        ]);

        $year = $request->input('year');
        $month = $request->input('month');

        $query = GoodsDonation::query();

        if ($year) {
            $query->where('year', $year);
        }

        if ($month) {
            $query->where('month', $month);
        }

        $nearExpirationDays = (int) ($validated['near_expiration_days'] ?? 0);

        if ($nearExpirationDays > 0) {
            $today = now()->toDateString();
            $until = now()->addDays($nearExpirationDays)->toDateString();

            $query->whereHas('items', function ($itemQuery) use ($today, $until) {
                $itemQuery->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', $today)
                    ->whereDate('expiry_date', '<=', $until);
            });
        }

        $donations = $query->with('items')
                            ->orderBy('created_at', 'desc')
                            ->get()
                            ->loadCount('items');

        return response()->json($donations);
    }

    /**
     * Search goods donations by name, email, description, address, month, or year.
     * Example: /api/goods-donations/search?q=bag
     */
    public function search(Request $request)
    {
        $search = $request->input('q');

        if (!$search) {
            return response()->json([], 200);
        }

        $donations = GoodsDonation::where(function ($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('address', 'like', "%{$search}%")
                ->orWhere('month', 'like', "%{$search}%")
                ->orWhere('year', 'like', "%{$search}%");
        })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($donations);
    }

    /**
     * Get the number of goods donations per month for a given year.
     * Example: /api/goods-donations/stats?year=2025
     */
    public function stats(Request $request)
    {
        $year = $request->input('year', now()->year); // Default to current year

        $data = GoodsDonation::select('month', DB::raw('COUNT(*) as total'))
            ->where('year', $year)
            ->where('status', 'approved')
            ->groupBy('month')
            ->orderByRaw("STR_TO_DATE(CONCAT('01 ', month, ' {$year}'), '%d %M %Y')")
            ->get();

        // Ensure months appear in order
        $months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December'
        ];

        $formatted = collect($months)->map(function ($m) use ($data) {
            $record = $data->firstWhere('month', $m);
            return [
                'month' => $m,
                'total' => $record ? $record->total : 0
            ];
        });

        return response()->json([
            'year' => $year,
            'data' => $formatted
        ]);
    }

    /**
     * Get total count of all approved goods donations.
     */
    public function counts()
    {
        $count = GoodsDonation::where('status', 'approved')->count();

        return response()->json([
            'status' => 'success',
            'total_approved_goods_donations' => $count,
        ]);
    }

    /**
     * Approve a specific cash donation by ID.
     * Example: PUT /api/goods-donations/{id}/approve
     */
    public function approve($id)
    {
        try {
            $donation = GoodsDonation::findOrFail($id);

            if ($donation->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This donation is already approved.',
                ], 400);
            }

            $donation->update(['status' => 'approved']);

            return response()->json([
                'status' => 'success',
                'message' => 'Donation approved successfully.',
                'data' => $donation,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Donation not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Unexpected error while approving goods donation.', [
                'donation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while approving donation.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Approve a specific cash donation by ID.
     * Example: PUT /api/cash-donations/{id}/approve
     */
    public function confirm($id)
    {
        try {
            $donation = GoodsDonation::findOrFail($id);

            if ($donation->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This donation is already approved.',
                ], 400);
            }

            $donation->update(['status' => 'approved']);

            return response()->json([
                'status' => 'success',
                'message' => 'Donation approved successfully.',
                'data' => $donation,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Donation not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Unexpected error while approving goods donation (v2).', [
                'donation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while approving donation.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Approve a goods donation and email donor a receipt-style confirmation with items.
     */
    public function testConfirmation($id)
    {
        try {
            $donation = GoodsDonation::with('items')->findOrFail($id);

            if ($donation->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This donation is already approved.',
                ], 400);
            }

            DB::transaction(function () use ($donation) {
                $donation->update(['status' => 'approved']);
                $donation->items()->update(['is_confirmed' => true]);
                $donation->refresh()->load('items');
                $this->inventoryService->syncApprovedDonation($donation);
            });

            $donation->refresh()->load('items');


            if ($donation->email) {
                try {
                    $itemsList = $donation->items->map(function ($item) {
                        $quantity = $item->quantity ?? 0;
                        $unit = $item->unit ? " {$item->unit}" : '';
                        return "- {$item->name} ({$quantity}{$unit})";
                    })->implode("\n");

                    $body = "Hello {$donation->name},\n\n"
                        . "We have received your goods donation. Thank you for your generosity.\n\n"
                        . "Items received:\n{$itemsList}\n\n"
                        . "If anything looks incorrect, please let us know.";

                    Mail::raw($body, function ($message) use ($donation) {
                        $message->to($donation->email)->subject('Goods donation received');
                    });
                } catch (Exception $mailException) {
                    Log::warning('Failed to send donor confirmation email during goods donation approval.', [
                        'donation_id' => $id,
                        'error' => $mailException->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Donation approved and donor notified.',
                'data' => $donation->fresh('items'),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Donation not found.',
            ], 404);
        } catch (Exception $e) {
            Log::error('Unexpected error while approving goods donation (v2 with inventory sync/email).', [
                'donation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while approving donation.',
                'debug' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Reject a goods donation and notify the donor with next steps.
     */
    public function reject(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string',
            ]);

            $donation = GoodsDonation::findOrFail($id);

            if ($donation->status === 'rejected') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This donation is already rejected.',
                ], 400);
            }

            if ($donation->status === 'approved') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This donation is already approved.',
                ], 400);
            }

            $donation->update(attributes: [
                'status' => 'rejected',
                'reject_reason' => $validated['reason'] ?? null,
            ]);

            if ($donation->email) {
                $contact = ContactInfo::first();
                $contactLine = [];
                if ($contact?->telephone_number) {
                    $contactLine[] = "Tel: {$contact->telephone_number}";
                }
                if ($contact?->phone_number) {
                    $contactLine[] = "Mobile: {$contact->phone_number}";
                }
                if ($contact?->email_address) {
                    $contactLine[] = "Email: {$contact->email_address}";
                }
                if ($contact?->physical_address) {
                    $contactLine[] = "Address: {$contact->physical_address}";
                }

                $contactText = $contactLine ? implode("\n", $contactLine) : 'Please contact Kalinga ng Kababaihan management for assistance.';

                $reasonLine = !empty($validated['reason'])
                    ? "Reason stated by Kalinga ng Kababaihan: {$validated['reason']}\n\n"
                    : '';

                $body = "Hello {$donation->name},\n\n"
                    . "Thank you for your willingness to donate. We truly appreciate your generosity.\n\n"
                    . "At this time, we are unable to accept your goods donation. If you would like to discuss alternative arrangements or receive guidance on how to proceed, please contact Kalinga ng Kababaihan management:\n"
                    . $reasonLine
                    . "{$contactText}\n\n"
                    . "Thank you again for your support.";

                Mail::raw($body, function ($message) use ($donation) {
                    $message->to($donation->email)->subject('Goods donation update');
                });
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Donation rejected and donor notified.',
                'data' => $donation,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Donation not found.',
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while rejecting donation.',
            ], 500);
        }
    }

    public function goodsDonations(Request $request)
    {
        $query = GoodsDonation::query();

        $from = $request->input('dateFrom');
        $to = $request->input('dateTo');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])->where('status', 'approved');
        }

        $donations = $query->get();

        return response()->json([
            'donations' => $donations,
            'totalAmount' => number_format($donations->sum('amount'), 2, '.', ','),
            'totalCount' => $donations->count(),
        ]);
    }

    /**
     * Suggest item names for goods donations.
     * Example: /api/goods-donations/suggestions?count=10&seed=food
     */
    public function suggestItems(Request $request)
    {
        $validated = $request->validate([
            'count' => 'nullable|integer|min:3|max:20',
            'seed' => 'nullable|string|max:100',
        ]);

        $count = $validated['count'] ?? 10;
        $seed = $validated['seed'] ?? '';

        $fallback = [
            'Rice',
            'Canned sardines',
            'Instant noodles',
            'Canned corned beef',
            'Bottled water',
            'Sugar',
            'Coffee',
            'Cooking oil',
            'Bath soap',
            'Toothpaste',
            'Laundry detergent',
            'Diapers',
            'Blankets',
            'School supplies',
            'Face masks',
        ];

        try {
            $prompt = "Provide {$count} highly realistic goods donation item names commonly donated in the Philippines."
                . " Prefer everyday relief items actually donated by individuals and community drives (e.g., groceries, hygiene, baby items, household essentials, school supplies)."
                . " Use natural item names (no brand names unless they are commonly used as generic terms)."
                . ($seed !== '' ? " Focus on: {$seed}." : '')
                . " Return only a JSON array of strings (no extra text).";

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . env('GROQ_API_KEY'),
            ])->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                $text = $responseData['choices'][0]['message']['content'] ?? '';
                $decoded = json_decode($text, true);

                if (is_array($decoded)) {
                    $suggestions = array_values(array_unique(array_filter($decoded, 'is_string')));
                    if (!empty($suggestions)) {
                        return response()->json([
                            'suggestions' => array_slice($suggestions, 0, $count),
                        ], 200);
                    }
                }
            } else {
                Log::error('Groq API Error (suggestItems)', ['response' => $response->body()]);
            }
        } catch (Exception $e) {
            Log::error('Groq API Request Failed (suggestItems)', ['message' => $e->getMessage()]);
        }

        return response()->json([
            'suggestions' => array_slice($fallback, 0, $count),
        ], 200);
    }
}
