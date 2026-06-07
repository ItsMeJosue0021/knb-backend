<!DOCTYPE html>
<html lang="en">
@php
    $logoUrl = rtrim((string) config('app.url'), '/') . '/logo.png';
@endphp
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine ?? 'Kalinga ng Kababaihan' }}</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1f1f1f; background-color: #f6f7fb; padding: 0; margin: 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f6f7fb; padding: 24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width: 600px; max-width: 100%; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 6px 20px rgba(0,0,0,0.08);">
                    <tr>
                        <td align="center" style="background-color: #F97316; padding: 22px 24px;">
                            <img src="{{ $logoUrl }}" alt="Kalinga ng Kababaihan Logo" style="display: block; width: 72px; height: 72px; max-width: 72px; border-radius: 50%; margin: 0 auto 10px auto;">
                            <p style="font-size: 20px; line-height: 1.2; margin: 0; color: #ffffff; font-weight: 700;">Kalinga ng Kababaihan</p>
                            <p style="font-size: 12px; line-height: 1.4; margin: 4px 0 0 0; color: #fff7ed;">Women's League Las Pinas</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 24px; font-size: 14px; line-height: 1.7; color: #374151;">
                            {!! nl2br(e($messageContent)) !!}
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #fff7ed; padding: 16px 24px; color: #9a3412; font-size: 12px; text-align: center;">
                            <p style="margin: 0;">&copy; {{ date('Y') }} Kalinga ng Kababaihan LPC. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
