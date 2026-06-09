@props(['url'])
@php
    $logoUrl = config('app.email_logo_url');
@endphp
<tr>
<td class="header" style="padding: 25px 0; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
<img src="{{ $logoUrl }}" class="logo" alt="Kalinga ng Kababaihan Logo" style="height: 75px; max-height: 75px; width: 75px;">
<div style="color: #f97316; font-size: 18px; font-weight: 700; margin-top: 8px;">
Kalinga ng Kababaihan
</div>
</a>
</td>
</tr>
