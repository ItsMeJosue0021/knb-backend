<?php

namespace App\Mail\Concerns;

use App\Models\WebsiteLogo;
use Illuminate\Support\Facades\Storage;

trait ResolvesEmailLogo
{
    /**
     * Resolve a local logo file path that can be embedded in the email.
     */
    protected function resolveLogoPath(): ?string
    {
        $websiteLogo = WebsiteLogo::query()->first();

        if ($websiteLogo?->image_path) {
            $storedLogoPath = Storage::disk('public')->path($websiteLogo->image_path);
            if (is_file($storedLogoPath)) {
                return $storedLogoPath;
            }
        }

        $publicLogoPath = public_path('logo.png');
        if (is_file($publicLogoPath)) {
            return $publicLogoPath;
        }

        return null;
    }

    /**
     * View data the shared email partials use to render the branded logo header.
     */
    protected function logoViewData(): array
    {
        return [
            'logoPath' => $this->resolveLogoPath(),
            'fallbackLogoUrl' => config('app.email_logo_url'),
        ];
    }
}
