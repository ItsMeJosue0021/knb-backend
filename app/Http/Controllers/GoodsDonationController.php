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
     * Example: /api/goods-donations/v2/suggestions?count=10&category=Food&subcategory=Rice
     */
    public function suggestItems(Request $request)
    {
        $validated = $request->validate([
            'count' => 'nullable|integer|min:3|max:20',
            'seed' => 'nullable|string|max:255',
            'q' => 'nullable|string|max:255',
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'subcategory' => 'nullable|string|max:255',
        ]);

        $count = $validated['count'] ?? 10;
        $query = trim($validated['q'] ?? ($validated['search'] ?? ''));
        $category = trim($validated['category'] ?? '');
        $subcategory = trim($validated['subcategory'] ?? '');
        $seedParts = array_values(array_filter([$category, $subcategory, trim($validated['seed'] ?? '')]));
        $seed = implode(' / ', array_unique($seedParts));

        $fallback = $this->filterSuggestionList(
            $this->buildFallbackSuggestions($category, $subcategory),
            $query,
            $count
        );

        try {
            $prompt = "Provide {$count} highly realistic goods donation item names commonly donated in the Philippines."
                . " Prefer everyday relief items actually donated by individuals and community drives."
                . " Use natural item names (no brand names unless they are commonly used as generic terms)."
                . ($category !== '' ? " The selected category is {$category}." : '')
                . ($subcategory !== '' ? " The selected subcategory is {$subcategory}." : '')
                . ($query !== '' ? " The donor has already typed '{$query}', so only return suggestions that match that text while staying inside the selected category and subcategory." : '')
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
                    $suggestions = $this->filterSuggestionList($decoded, $query, $count);
                    if (!empty($suggestions)) {
                        if (!empty($fallback) && count($suggestions) < $count) {
                            $suggestions = $this->filterSuggestionList(array_merge($suggestions, $fallback), $query, $count);
                        }

                        return response()->json([
                            'suggestions' => $suggestions,
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
            'suggestions' => $fallback,
        ], 200);
    }

    private function buildFallbackSuggestions(string $categoryName = '', string $subcategoryName = ''): array
    {
        $catalog = [
            'food' => [
                '__default' => ['Rice', 'Canned sardines', 'Instant noodles', 'Coffee sachets', 'Biscuits'],
                'rice' => ['Well-milled rice', 'Brown rice', 'Jasmine rice', 'Rice packs', 'Rice sacks'],
                'noodles pasta' => ['Spaghetti pasta', 'Macaroni pasta', 'Egg noodles', 'Rice noodles', 'Miswa noodles'],
                'canned goods sardines corned beef meat loaf' => ['Canned sardines', 'Canned corned beef', 'Canned meat loaf', 'Canned tuna', 'Canned beans'],
                'instant noodles' => ['Cup noodles', 'Pancit canton', 'Chicken instant noodles', 'Beef instant noodles', 'Vegetable instant noodles'],
                'instant meals ready to eat food' => ['Instant oatmeal', 'Cup soup', 'Ready-to-eat arroz caldo', 'Ready-to-eat lugaw', 'Packed instant meals'],
                'canned fish meat' => ['Canned tuna', 'Canned mackerel', 'Canned chicken spread', 'Canned luncheon meat', 'Canned corned beef'],
                'coffee' => ['3-in-1 coffee sachets', 'Instant coffee', 'Ground coffee', 'Coffee creamer', 'Sugar sticks'],
                'powdered drinks' => ['Powdered chocolate drink', 'Orange juice powder', 'Electrolyte drink powder', 'Powdered milk drink', 'Chocolate malt drink'],
                'juice canned drinks' => ['Boxed juice', 'Canned pineapple juice', 'Canned orange juice', 'Fruit drink tetra packs', 'Ready-to-drink chocolate milk'],
                'snacks biscuits' => ['Crackers', 'Oatmeal biscuits', 'Assorted cookies', 'Wafer snacks', 'Saltine biscuits'],
                'baby food formula' => ['Infant formula', 'Toddler milk', 'Baby cereal', 'Baby food puree', 'Baby oatmeal'],
                'bottled water' => ['500ml bottled water', '1-liter bottled water', 'Mineral water', 'Purified drinking water', 'Drinking water packs'],
            ],
            'clothings' => [
                '__default' => ['Adult t-shirts', 'Children clothing', 'Jackets', 'Blankets', 'Footwear'],
                'men tops' => ["Men's t-shirts", "Men's polo shirts", "Men's long-sleeve shirts", "Men's button-down shirts", "Men's sleeveless shirts"],
                'men bottoms' => ["Men's jeans", "Men's shorts", "Men's jogger pants", "Men's slacks", "Men's cargo pants"],
                'women tops' => ["Women's blouses", "Women's t-shirts", "Women's long-sleeve tops", "Women's polo shirts", "Women's sleeveless tops"],
                'women bottoms' => ["Women's jeans", "Women's leggings", "Women's skirts", "Women's slacks", "Women's shorts"],
                'children infants 0 2 yrs' => ['Infant onesies', 'Baby shirts', 'Baby shorts', 'Baby mittens', 'Baby socks'],
                'children toddlers 3 5 yrs' => ['Toddler shirts', 'Toddler shorts', 'Toddler dresses', 'Toddler pajamas', 'Toddler sandals'],
                'children kids 6 12 yrs' => ['Kids t-shirts', 'Kids shorts', 'Kids jeans', 'Kids dresses', 'Kids school clothes'],
                'school uniforms' => ['School uniform sets', 'White polo shirts', 'School skirts', 'School pants', 'PE uniforms'],
                'jackets sweaters' => ['Hooded jackets', 'Cardigans', 'Sweaters', 'Windbreakers', 'Fleece jackets'],
                'sleepwear' => ['Adult pajamas', 'Kids pajamas', 'Nightgowns', 'Sleep shirts', 'Pajama sets'],
                'footwear' => ['Rubber slippers', 'School shoes', 'Rubber shoes', 'Sandals', 'Sneakers'],
                'undergarments' => ['Children underwear', 'Adult underwear', 'Undershirts', 'Socks', 'Brassieres'],
                'bags belts' => ['Backpacks', 'Sling bags', 'Waist belts', 'School bags', 'Reusable tote bags'],
                'bedding blankets bedsheets' => ['Blankets', 'Bedsheets', 'Pillowcases', 'Comforters', 'Sleeping mats'],
                'towels' => ['Bath towels', 'Face towels', 'Hand towels', 'Baby towels', 'Washcloths'],
            ],
            'school supplies' => [
                '__default' => ['Notebooks', 'Ballpens', 'Pencils', 'Crayons', 'School bags'],
                'notebooks' => ['Spiral notebooks', 'Composition notebooks', 'Intermediate pads', 'Writing notebooks', 'Science notebooks'],
                'writing tools pens pencils' => ['Ballpens', 'Pencils', 'Colored pens', 'Markers', 'Highlighters'],
                'paper products bond paper pad paper' => ['Bond paper', 'Pad paper', 'Construction paper', 'Index cards', 'Colored paper'],
                'school bags' => ['Backpacks', 'Trolley school bags', 'Drawstring bags', 'Lunch bags', 'School satchels'],
                'lunch boxes' => ['Lunch boxes', 'Food containers', 'Water tumblers', 'Insulated lunch bags', 'Reusable utensils'],
                'art materials crayons coloring materials' => ['Crayons', 'Colored pencils', 'Watercolor sets', 'Oil pastels', 'Sketch pads'],
                'learning materials books workbooks' => ['Story books', 'Workbooks', 'Alphabet books', 'Math activity books', 'Reading books'],
                'school kits pre packed sets' => ['School supply kits', 'Notebook and pen sets', 'Student starter kits', 'Art starter kits', 'Reading kits'],
                'teaching supplies' => ['Whiteboard markers', 'Flash cards', 'Pocket charts', 'Class record books', 'Teaching posters'],
            ],
        ];

        $generic = ['Rice', 'Canned sardines', 'Instant noodles', 'Blankets', 'Notebooks'];
        $categoryKey = $this->normalizeSuggestionKey($categoryName);
        $subcategoryKey = $this->normalizeSuggestionKey($subcategoryName);

        if ($categoryKey === '' && $subcategoryKey === '') {
            return $generic;
        }

        $suggestions = [];
        $categorySuggestions = $catalog[$categoryKey] ?? [];

        if ($subcategoryKey !== '' && isset($categorySuggestions[$subcategoryKey])) {
            $suggestions = array_merge($suggestions, $categorySuggestions[$subcategoryKey]);
        }

        if (empty($suggestions) && $subcategoryKey !== '') {
            foreach ($categorySuggestions as $key => $values) {
                if ($key === '__default') {
                    continue;
                }

                if (str_contains($key, $subcategoryKey) || str_contains($subcategoryKey, $key)) {
                    $suggestions = array_merge($suggestions, $values);
                }
            }
        }

        if (isset($categorySuggestions['__default'])) {
            $suggestions = array_merge($suggestions, $categorySuggestions['__default']);
        }

        return !empty($suggestions) ? $suggestions : [];
    }

    private function filterSuggestionList(array $suggestions, string $query, int $count): array
    {
        $normalized = [];

        foreach ($suggestions as $suggestion) {
            $name = trim((string) $suggestion);
            if ($name === '') {
                continue;
            }

            $normalized[strtolower($name)] = $name;
        }

        $ordered = array_values($normalized);
        $query = strtolower(trim($query));

        if ($query !== '') {
            $prefixMatches = [];
            $containsMatches = [];

            foreach ($ordered as $suggestion) {
                $value = strtolower($suggestion);
                if (!str_contains($value, $query)) {
                    continue;
                }

                if (str_starts_with($value, $query)) {
                    $prefixMatches[] = $suggestion;
                    continue;
                }

                $containsMatches[] = $suggestion;
            }

            $ordered = array_merge($prefixMatches, $containsMatches);
        }

        return array_slice($ordered, 0, $count);
    }

    private function normalizeSuggestionKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }
}
