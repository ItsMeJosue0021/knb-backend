<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; }
        .container { background: #ffffff; border-radius: 6px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.05); overflow: hidden; }
        .content { padding: 20px; }
        h2 { color: #F97316; }
        p { font-size: 16px; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        @include('emails.partials.logo-header')
        <div class="content">
            <h2>Upcoming Cash Donation</h2>
            <p><strong>{{ $donation->name ?? 'Someone' }}</strong> will be donating <strong>₱{{ number_format($donation->amount, 2) }}</strong> in cash at <strong>{{ $donation->address ?? 'your office' }}</strong>.</p>
            <p>Please prepare to receive the donation accordingly.</p>
        </div>
    </div>
</body>
</html>
