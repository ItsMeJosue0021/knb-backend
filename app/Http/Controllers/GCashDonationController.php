<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use App\Models\GCashDonation;
use App\Services\DonationService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveGCashDonationRequest;

class GCashDonationController extends Controller
{
    protected $donationService;

    public function __construct(DonationService $donationService)
    {
        $this->donationService = $donationService;
    }

    public function store(SaveGCashDonationRequest $request)
    {
        try {
            $validated = $request->validated();

            $result = $this->donationService->processGCashDonation($validated);

            if (($validated['payment_channel'] ?? 'gateway') === 'qr') {
                return response()->json([
                    'message' => 'GCash QR donation submitted successfully and is pending verification.',
                    'donation' => $result,
                ], 201);
            }

            return response()->json($result, 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'GCash donation was unsuccessfully. Please try again.',
            ], 500);
        }
    }

    /**
     * Retrieve all donations.
     */
    public function index()
    {
        $donations = GCashDonation::orderBy('created_at', 'desc')->get();
        return response()->json($donations);
    }

    /**
     * Filter donations by year, month, or both.
     * Example: /api/gcash-donations/filter?year=2025&month=October
     */
    public function filter(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $query = GCashDonation::query();

        if ($year) {
            $query->where('year', $year);
        }

        if ($month) {
            $query->where('month', $month);
        }

        $donations = $query->orderBy('created_at', 'desc')->get();

        return response()->json($donations);
    }

    /**
     * Search donations by tracking number, name, email, amount, month, or year.
     * Example: /api/gcash-donations/search?q=john
     */
    public function search(Request $request)
    {
        $search = $request->input('q');

        if (!$search) {
            return response()->json([], 200);
        }

        $donations = GCashDonation::where(function ($query) use ($search) {
            $query->where('donation_tracking_number', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('payment_channel', 'like', "%{$search}%")
                ->orWhere('payment_reference_number', 'like', "%{$search}%")
                ->orWhere('month', 'like', "%{$search}%")
                ->orWhere('year', 'like', "%{$search}%");
        })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($donations);
    }

    /**
     * Get the number of donations per month for a given year.
     * Example: /api/gcash-donations/stats?year=2025
     */
    public function stats(Request $request)
    {
        $year = $request->input('year', now()->year); // Default to current year

        // Group donations by month for that year
        $data = GCashDonation::select('month', DB::raw('COUNT(*) as total'))
            ->where('year', $year)
            ->where('status', 'paid')
            ->groupBy('month')
            ->orderByRaw("STR_TO_DATE(CONCAT('01 ', month, ' 2025'), '%d %M %Y')")
            ->get();

        // Ensure months appear in proper order and missing ones are filled with 0
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

    public function counts()
    {
        $count = GCashDonation::where('status', 'paid')->count();
        $totalAmount = GCashDonation::where('status', 'paid')->sum('amount');

        return response()->json([
            'status' => 'success',
            'total_paid_donations' => $count,
            'total_paid_amount' => $totalAmount,
        ]);
    }

    public function gcashDonations(Request $request)
    {
        $query = GCashDonation::where('status', 'paid');

        $from = $request->input('dateFrom');
        $to = $request->input('dateTo');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);
        }

        $donations = $query->get();

        return response()->json([
            'donations' => $donations,
            'totalAmount' => $donations->sum('amount'),
            'totalCount' => $donations->count(),
        ]);
    }

    public function approve(int $id)
    {
        try {
            $donation = $this->donationService->confirmQrGCashDonation($id);

            return response()->json([
                'status' => 'success',
                'message' => 'GCash QR donation confirmed successfully.',
                'data' => $donation,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to confirm the GCash QR donation.',
            ], 500);
        }
    }

}
