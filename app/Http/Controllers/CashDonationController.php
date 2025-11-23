<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\CashDonation;
use Illuminate\Http\Request;
use App\Services\DonationService;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveCashDonationRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CashDonationController extends Controller
{
    protected $donationService;

    public function __construct(DonationService $donationService)
    {
        $this->donationService = $donationService;
    }

    public function store(SaveCashDonationRequest $request)
    {
        try {
            $validated = $request->validated();

            $donation = $this->donationService->processCashDonation($validated);

            return response()->json([
                'donation' => $donation,
                'message' => 'Cash donation recorded successfully',
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Cash donation was unuccessfully. Please try again.',
            ], 500);
        }
    }

    public function confirmCashDonation($id)
    {
        $success = $this->donationService->confirmCashDonation($id);

        if ($success) {
            return response()->json([
                'message' => 'Cash donation status updated successfully',
            ], 200);
        } else {
                return response()->json([
                'message' => 'Failed to update cash donation status. Please try again.',
            ], 500);
        }
    }

    /**
     * Retrieve all approved cash donations.
     */
    public function index()
    {
        $donations = CashDonation::orderBy('created_at', 'desc')
            ->get();

        return response()->json($donations);
    }

    /**
     * Filter donations by year, month, or both.
     * Example: /api/cash-donations/filter?year=2025&month=October
     */
    public function filter(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        $query = CashDonation::query();

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
     * Example: /api/cash-donations/search?q=john
     */
    public function search(Request $request)
    {
        $search = $request->input('q');

        if (!$search) {
            return response()->json([], 200);
        }

        $donations = CashDonation::where(function ($query) use ($search) {
            $query->where('donation_tracking_number', 'like', "%{$search}%")
                ->orWhere('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('month', 'like', "%{$search}%")
                ->orWhere('year', 'like', "%{$search}%");
        })
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($donations);
    }

    /**
     * Get the number of donations per month for a given year.
     * Example: /api/cash-donations/stats?year=2025
     */
    public function stats(Request $request)
    {
        $year = $request->input('year', now()->year); // Default to current year

        $data = CashDonation::select('month', DB::raw('COUNT(*) as total'))
            ->where('year', $year)
            ->where('status', 'approved')
            ->groupBy('month')
            ->orderByRaw("STR_TO_DATE(CONCAT('01 ', month, ' {$year}'), '%d %M %Y')")
            ->get();

        // Ensure all months appear in order
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
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
     * Get total count and amount of all approved donations.
     */
    public function counts()
    {
        $count = CashDonation::where('status', 'approved')->count();
        $totalAmount = CashDonation::where('status', 'approved')->sum('amount');

        return response()->json([
            'status' => 'success',
            'total_approved_donations' => $count,
            'total_approved_amount' => $totalAmount,
        ]);
    }


    /**
     * Approve a specific cash donation by ID.
     * Example: PUT /api/cash-donations/{id}/approve
     */
    public function approve($id)
    {
        try {
            $donation = CashDonation::findOrFail($id);

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
            return response()->json([
                'status' => 'error',
                'message' => 'An unexpected error occurred while approving donation.',
            ], 500);
        }
    }

    public function cashDonations(Request $request)
    {
        $query = CashDonation::query();

        $from = $request->input('dateFrom');
        $to = $request->input('dateTo');

        if ($from && $to) {
            $query->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
                ->where('status', 'approved');
        } else {
            $query->where('status', 'approved');
        }

        $donations = $query->get();

        return response()->json([
            'donations' => $donations,
            'totalAmount' => number_format($donations->sum('amount'), 2, '.', ','),
            'totalCount' => $donations->count(),
        ]);
    }
}
