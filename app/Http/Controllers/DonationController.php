<?php

namespace App\Http\Controllers;

use Exception;
use App\Mail\KalingaEmail;
use App\Models\Donation;
use Illuminate\Http\Request;
use App\Models\GoodsDonation;
use App\Jobs\SendDonationEmails;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;

class DonationController extends Controller
{
    // public function index()
    // {
    //     $donations = Donation::all();
    //     return response()->json($donations, );
    // }

    public function index(Request $request)
    {
        $query = Donation::query();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        // if ($request->has('name')) {
        //     $query->where('name', 'like', '%' . $request->input('name') . '%');
        // }

        if ($request->has('name')) {
            $search = $request->input('name');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('reference', 'like', '%' . $search . '%')
                    ->orWhere('amount', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('month')) {
            // Filter by month name directly (e.g., "January")
            $query->where('month', $request->input('month'));
        }

        if ($request->has('year')) {
            // Filter by year directly
            $query->where('year', $request->input('year'));
        }

        $donations = $query->latest()->get();

        return response()->json($donations);
    }




    public function store(Request $request)
    {
        $type = $request->input('type');

        $rules = [
            'type' => 'required|string',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'amount' => 'required|numeric',
            'address' => 'required', // for cash donations
        ];

        if ($type === 'gcash') {
            $rules['reference'] = 'required|string';
            $rules['proof'] = 'required|image|max:2048';
        }

        $validated = $request->validate($rules);

        if ($request->hasFile('proof')) {
            $validated['proof'] = $request->file('proof')->store('donations', 'public');
        }

        $validated['year'] = now()->year;
        $validated['month'] = now()->format('F');

        $donation = Donation::create($validated);

        // SendDonationEmails::dispatch($donation);

        $adminEmail = 'margeiremulta@gmail.com';

        // Format values
        $name = $donation->name ?? 'Someone';
        $amount = number_format($donation->amount, 2);

        if ($type === 'gcash') {
            // Email to admin
            Mail::to($adminEmail)->send(new KalingaEmail(
                'New GCash Donation',
                "$name has donated ₱$amount through GCash."
            ));

            // Email to donor
            if ($donation->email) {
                Mail::to($donation->email)->send(new KalingaEmail(
                    'Donation Received',
                    "We have received your donation. Thank you and may God bless you."
                ));
            }

        } elseif ($type === 'cash') {
            // Email to admin
            $address = $donation->address ?? 'office.'; // you can also make this dynamic
            Mail::to($adminEmail)->send(new KalingaEmail(
                'Upcoming Cash Donation',
                "$name will be donating ₱$amount in cash at your $address."
            ));

            // Email to donor
            if ($donation->email) {
                Mail::to($donation->email)->send(new KalingaEmail(
                    'Donation Instructions',
                    "Please proceed to the chosen address to hand in your cash donation. Thank you so much."
                ));
            }
        }

        return response()->json(['message' => 'Donation saved successfully.'], 201);
    }

    public function update(Request $request, $id)
    {
        $donation = Donation::findOrFail($id);
        if (!$donation) {
            return response()->json(['error' => 'Donation not found.'], 404);
        }

        $type = $request->input('type');

        $rules = [
            'type' => 'required|string',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'amount' => 'required|numeric',
        ];


        if ($type === 'gcash') {
            $rules['reference'] = 'required|string';
            $rules['proof'] = 'nullable|image|max:2048';
        }

        $validated = $request->validate($rules);

        $validated['year'] = now()->year;
        $validated['month'] = now()->format('F');

        if ($request->hasFile('proof')) {
            $validated['proof'] = $request->file('proof')->store('donations', 'public');
        }

        try {
            $donation->update($validated);
            return response()->json(['message' => 'Donation updated successfully.'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => $e], 500);
        }
    }

    public function getDonationSummary()
    {
        $totalGcash = Donation::where('type', 'gcash')->sum('amount');
        $totalCash = Donation::where('type', 'cash')->sum('amount');
        $totalCount = Donation::count();

        $monthly = DB::table('donations')
            ->select(
                'year',
                'month',
                DB::raw('SUM(amount) as totalAmount'),
                DB::raw('COUNT(*) as numberOfDonations')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'month' => $item->month,
                    'totalAmount' => number_format($item->totalAmount, 2),
                    'numberOfDonations' => $item->numberOfDonations,
                ];
            });

        $yearly = DB::table('donations')
            ->select(
                'year',
                DB::raw("SUM(CASE WHEN type = 'cash' THEN amount ELSE 0 END) as cash"),
                DB::raw("SUM(CASE WHEN type = 'gcash' THEN amount ELSE 0 END) as gcash"),
                DB::raw("SUM(amount) as total")
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'cash' => number_format($item->cash, 2),
                    'gcash' => number_format($item->gcash, 2),
                    'total' => number_format($item->total, 2),
                ];
            });

        // $monthlyTrend = GoodsDonation::select(
        //     'year',
        //     'month',
        //     'type',
        //     DB::raw('COUNT(*) as count')
        // )
        //     ->groupBy('year', 'month', 'type')
        //     ->orderBy('year')
        //     ->orderBy('month')
        //     ->get();

        $monthlyTrend = GoodsDonation::select('year', 'month', 'type')->get();

        $monthlyTrend = $monthlyTrend->groupBy(function ($item) {
            // If type is already an array, no need to decode
            $typesArray = is_array($item->type) ? $item->type : json_decode($item->type, true);

            sort($typesArray); // Normalize the order of the array
            $normalizedType = json_encode($typesArray); // Convert back to string for grouping

            return $item->year . '-' . $item->month . '-' . $normalizedType;
        })->map(function ($group) {
            $first = $group->first();
            $typesArray = is_array($first->type) ? $first->type : json_decode($first->type, true);

            return [
                'year' => $first->year,
                'month' => $first->month,
                'type' => $typesArray, // Return as array
                'count' => $group->count(),
            ];
        })->sortBy([
                    ['year', 'asc'],
                    ['month', 'asc'],
                ])->values();



        // GOODS DONATIONS
        $goodsYearly = DB::table('goods_donations')
            ->select(
                'year',
                DB::raw("SUM(CASE WHEN type = 'clothes' THEN 1 ELSE 0 END) as clothes"),
                DB::raw("SUM(CASE WHEN type = 'food' THEN 1 ELSE 0 END) as food"),
                DB::raw("SUM(CASE WHEN type = 'groceries' THEN 1 ELSE 0 END) as groceries"),
                DB::raw("COUNT(*) as total")
            )
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->map(function ($item) {
                return [
                    'year' => $item->year,
                    'clothes' => (int) $item->clothes,
                    'food' => (int) $item->food,
                    'groceries' => (int) $item->groceries,
                    'total' => (int) $item->total,
                ];
            });

        $goodsTotalCount = GoodsDonation::count();

        return response()->json([
            'totalGcash' => number_format($totalGcash, 2, '.', ','),
            'totalCash' => number_format($totalCash, 2, '.', ','),
            'totalCount' => (string) $totalCount,
            'monthlyTrend' => $monthly,
            'yearlyComparison' => $yearly,
            'goodsMonthlyTrend' => $monthlyTrend,
            'goodsYearlySummary' => $goodsYearly,
            'goodsDonTotal' => $goodsTotalCount
        ]);
    }


}
