<?php

namespace App\Services;

use App\Mail\KalingaEmail;
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

    /**
     * Map a chosen drop-off option to its full street address.
     */
    private function resolveDropOffAddress(?string $choice): string
    {
        return match ($choice) {
            'Main Address' => 'B4 Lot 6-6 Fantasy Road 3, Teresa Park Subd., Pilar, Las Piñas City',
            'Satellite Address' => 'Block 20 Lot 15-A Mines View, Teresa Park Subd., Pilar, Las Piñas City',
            default => $choice ?: 'our office',
        };
    }

    /**
     * Describe a chosen drop-off option with its full street address,
     * e.g. "Main Address (B4 Lot 6-6 Fantasy Road 3, ...)".
     */
    private function describeDropOffAddress(?string $choice): string
    {
        $full = $this->resolveDropOffAddress($choice);

        return ($choice && $choice !== $full) ? "{$choice} ({$full})" : $full;
    }

    public function processCashDonation(array $data)
    {
        $adminEmail = 'margeiremulta@gmail.com';

        $data['year'] = now()->year;
        $data['month'] = now()->format('F');
        $donation = CashDonation::create($data);

        if ($donation) {
            $name = $donation->name ?? 'Someone';
            $amount = number_format($donation->amount, 2);
            $dropOff = $this->describeDropOffAddress($donation->drop_off_address);
            $fullAddress = $this->resolveDropOffAddress($donation->drop_off_address);

            Mail::to($adminEmail)->send(new KalingaEmail(
                'Upcoming Cash Donation',
                "$name will be donating ₱$amount in cash at $dropOff on {$donation->drop_off_date} at {$donation->drop_off_time}.\n\n"
                . "Please prepare to receive the donation accordingly."
            ));

            if ($donation->email) {
                Mail::to($donation->email)->send(new KalingaEmail(
                    'Your Cash Donation Instructions',
                    "Dear {$name},\n\n"
                    . "Thank you for your generous pledge to donate ₱{$amount} in cash to Kalinga ng Kababaihan.\n\n"
                    . "Here are your drop-off details:\n"
                    . "Location: {$fullAddress}\n"
                    . "Date: {$donation->drop_off_date}\n"
                    . "Time: {$donation->drop_off_time}\n\n"
                    . "Please proceed to the address above on your chosen schedule to hand in your donation. "
                    . "If you need to reschedule or have any questions, simply reply to this email and we will gladly assist you.\n\n"
                    . "Your support means so much to the women and families we serve. Thank you, and may God bless you abundantly!"
                ));
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

            Mail::to($adminEmail)->send(new KalingaEmail(
                'GCash QR Donation Pending Verification',
                "$name submitted a GCash QR donation of ₱$amount with tracking number $donation->donation_tracking_number and reference number $reference. Please review and confirm it in the admin panel."
            ));

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
            Mail::to($adminEmail)->send(new KalingaEmail(
                'New GCash Donation',
                "$name has donated ₱$amount through GCash with donation tracking number $donation->donation_tracking_number."
            ));

            if ($donation->email) {
                Mail::to($donation->email)->send(new KalingaEmail(
                    'GCash Donation Received',
                    "We have received your GCash donation. Thank you and may God bless you! "
                ));
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

        Mail::to($adminEmail)->send(new KalingaEmail(
            'GCash QR Donation Confirmed',
            "The GCash QR donation with tracking number $donation->donation_tracking_number has been confirmed and marked as paid."
        ));

        if ($donation->email) {
            Mail::to($donation->email)->send(new KalingaEmail(
                'GCash Donation Confirmed',
                "We have confirmed your GCash donation of ₱$amount. Thank you so much for supporting Kalinga ng Kababaihan."
            ));
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
            Mail::to($adminEmail)->send(new KalingaEmail(
                'Cash Donation Received',
                "The cash donation with tracking number $donation->donation_tracking_number has been received."
            ));

            if ($donation->email) {
                Mail::to($donation->email)->send(new KalingaEmail(
                    'Donation Received',
                    "We have received your cash donation. Thank you and may God bless you!"
                ));
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
