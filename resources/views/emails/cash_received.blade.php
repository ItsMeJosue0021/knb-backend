<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f2f2f2; padding: 20px; }
        .container { background: #ffffff; border-radius: 6px; max-width: 600px; margin: auto; box-shadow: 0 0 5px rgba(0,0,0,0.05); overflow: hidden; }
        .content { padding: 25px; }
        h2 { color: #F97316; }
        p { font-size: 16px; color: #444; line-height: 1.5; }
    </style>
</head>
<body>
    <div class="container">
        @include('emails.partials.logo-header')
        <div class="content">
            <h2>Donation Instructions</h2>
            <p>Dear {{ $donation->name ?? 'Donor' }},</p>
            <p>Thank you for pledging to donate <strong>₱{{ number_format($donation->amount, 2) }}</strong> in cash.</p>
            <p>Please proceed to <strong>{{ $donation->address ?? 'our office' }}</strong> to hand in your donation. We deeply appreciate your generosity.</p>
        </div>
    </div>
</body>
</html>
