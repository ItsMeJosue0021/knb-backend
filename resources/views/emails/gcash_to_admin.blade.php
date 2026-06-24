<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f9f9f9; padding: 20px; }
        .container { background: #ffffff; border-radius: 6px; max-width: 600px; margin: auto; box-shadow: 0 0 10px rgba(0,0,0,0.05); overflow: hidden; }
        .content { padding: 20px; }
        h2 { color: #4CAF50; }
        p { font-size: 16px; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        @include('emails.partials.logo-header')
        <div class="content">
            <h2>New GCash Donation Received</h2>
            <p><strong>{{ $donation->name ?? 'Someone' }}</strong> has donated <strong>₱{{ number_format($donation->amount, 2) }}</strong> through GCash.</p>
            <p>Please check the system or proof of donation for more details.</p>
        </div>
    </div>
</body>
</html>
