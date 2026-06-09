<?php

namespace App\Mail;

use App\Models\WebsiteLogo;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class KalingaEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $messageContent;

    public function __construct(public string $subjectLine, string $messageContent)
    {
        $this->messageContent = $messageContent;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.kalinga')
            ->with([
                'logoPath' => $this->resolveLogoPath(),
                'fallbackLogoUrl' => config('app.email_logo_url'),
            ]);
    }

    private function resolveLogoPath(): ?string
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
}
