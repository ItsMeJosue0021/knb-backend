@php
    $logoUrl = $fallbackLogoUrl ?? config('app.email_logo_url');
    if (!empty($logoPath) && is_file($logoPath) && isset($message)) {
        $logoUrl = $message->embed($logoPath);
    }
@endphp
<div style="text-align: center; background-color: #F97316; padding: 22px 24px; border-radius: 6px 6px 0 0;">
    <img src="{{ $logoUrl }}" alt="Kalinga ng Kababaihan Logo" style="display: inline-block; width: 72px; height: 72px; max-width: 72px; border-radius: 50%; margin: 0 auto 10px auto;">
    <p style="font-size: 20px; line-height: 1.2; margin: 0; color: #ffffff; font-weight: 700;">Kalinga ng Kababaihan</p>
    <p style="font-size: 12px; line-height: 1.4; margin: 4px 0 0 0; color: #fff7ed;">Women's League Las Piñas</p>
</div>
