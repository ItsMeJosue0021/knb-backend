<?php

namespace App\Services;

use App\Models\CashDonation;
use App\Models\GCashDonation;
use App\Services\PayMongoService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DonationService
{
    protected $paymongo;
    public function __construct(PayMongoService $paymongo)
    {
        $this->paymongo = $paymongo;
    }

    public function processCashDonation(array $data)
    {
        $adminEmail = 'margeiremulta@gmail.com';

        $data['year'] = now()->year;
        $data['month'] = now()->format('F');
        $donation = CashDonation::create($data);

        if ($donation) {
            $address = $donation->drop_off_address ?? 'office.';
            $name = $donation->name ?? 'Someone';
            $amount = number_format($donation->amount, 2);

            Mail::raw(
                "$name will be donating ₱$amount in cash at your $address on $donation->drop_off_date at $donation->drop_off_time.",
                function ($msg) use ($adminEmail) {
                    $msg->to($adminEmail)->subject('Upcoming Cash Donation');
                }
            );

            $address = '';
            switch ($donation->drop_off_address) {
                case "Main Address":
                    $address = "B4 Lot 6-6 Fantasy Road 3, Teresa Park Subd., Pilar, Las Piñas City";
                    break;
                case "Satellite Address":
                    $address = "Block 20 Lot 15-A Mines View, Teresa Park Subd., Pilar, Las Piñas City";
                    break;
                default:
                    $address = $donation->drop_off_address ?? 'office.';
                    break;
            }

            if ($donation->email) {
                Mail::raw(
                    "Please proceed to $address to hand in your cash donation on $donation->drop_off_date at $donation->drop_off_time. Thank you so much.",
                    function ($msg) use ($donation) {
                        $msg->to($donation->email)->subject('Donation Instructions');
                    }
                );
            }
        }

        return $donation;
    }

    public function processGCashDonation(array $data)
    {
        $adminEmail = 'margeiremulta@gmail.com';
        $channel = $data['payment_channel'] ?? 'gateway';

        if ($channel === 'qr') {
            $donation = GCashDonation::create([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'amount' => $data['amount'],
                'payment_channel' => 'qr',
                'payment_reference_number' => $data['payment_reference_number'] ?? null,
                'proof_of_payment' => isset($data['proof_of_payment'])
                    ? $this->storeGCashProof($data['proof_of_payment'])
                    : null,
                'month' => now()->format('F'),
                'year' => now()->year,
                'paymongo_id' => 'qr_manual_' . Str::uuid(),
                'status' => 'pending',
            ]);

            $name = $donation->name ?? 'Someone';
            $amount = number_format($donation->amount, 2);
            $reference = $donation->payment_reference_number ?? 'N/A';

            Mail::raw(
                "$name submitted a GCash QR donation of ₱$amount with tracking number $donation->donation_tracking_number and reference number $reference. Please review and confirm it in the admin panel.",
                function ($msg) use ($adminEmail) {
                    $msg->to($adminEmail)->subject('GCash QR Donation Pending Verification');
                }
            );

            return $donation;
        }

        $payment = $this->paymongo->createGCashSource(
            $data['amount'],
            url('https://www.kalingangkababaihan.com/donate/success'),
            url('https://www.kalingangkababaihan.com/donate/failed')
        );

        $donation = GCashDonation::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'amount' => $data['amount'],
            'payment_channel' => 'gateway',
            'month' => now()->format('F'),
            'year' => now()->year,
            'paymongo_id' => $payment['data']['id'],
            'status' => 'pending',
        ]);

        $name = $donation->name ?? 'Someone';
        $amount = number_format($donation->amount, 2);

        if ($donation) {
            Mail::raw(
                "$name has donated ₱$amount through GCash with donation tracking number $donation->donation_tracking_number.",
                function ($msg) use ($adminEmail) {
                    $msg->to($adminEmail)->subject('New GCash Donation');
                }
            );

            if ($donation->email) {
                Mail::raw(
                    "We have received your GCash donation. Thank you and may God bless you! ",
                    function ($msg) use ($donation) {
                        $msg->to($donation->email)->subject('GCash Donation Received');
                    }
                );
            }
        }

        return $payment;
    }

    public function confirmQrGCashDonation(int $id): GCashDonation
    {
        $donation = GCashDonation::findOrFail($id);

        if ($donation->payment_channel !== 'qr') {
            throw new \RuntimeException('Only QR GCash donations can be confirmed manually.');
        }

        if ($donation->status === 'paid') {
            return $donation;
        }

        $donation->status = 'paid';
        $donation->confirmed_at = now();
        $donation->save();

        $adminEmail = 'margeiremulta@gmail.com';
        $amount = number_format($donation->amount, 2);

        Mail::raw(
            "The GCash QR donation with tracking number $donation->donation_tracking_number has been confirmed and marked as paid.",
            function ($msg) use ($adminEmail) {
                $msg->to($adminEmail)->subject('GCash QR Donation Confirmed');
            }
        );

        if ($donation->email) {
            Mail::raw(
                "We have confirmed your GCash donation of ₱$amount. Thank you so much for supporting Kalinga ng Kababaihan.",
                function ($msg) use ($donation) {
                    $msg->to($donation->email)->subject('GCash Donation Confirmed');
                }
            );
        }

        return $donation;
    }

    public function confirmCashDonation($id)
    {
        try {
            $donation = CashDonation::findOrFail($id);
            if (!$donation) {
                return null;
            }

            $donation->status = 'confirmed';
            $donation->save();

            $adminEmail = 'margeiremulta@gmail.com';
            Mail::raw(
                "The cash donation with tracking number $donation->donation_tracking_number has been received.",
                function ($msg) use ($adminEmail) {
                    $msg->to($adminEmail)->subject('Cash Donation Received');
                }
            );

            if ($donation->email) {
                Mail::raw(
                    "We have received your cash donation. Thank you and may God bless you!",
                    function ($msg) use ($donation) {
                        $msg->to($donation->email)->subject('Donation Received');
                    }
                );
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }


    }

    private function storeGCashProof(UploadedFile $file): string
    {
        return $file->store('donations/gcash/proofs', 'public');
    }
}
