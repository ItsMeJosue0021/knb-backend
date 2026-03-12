<?php

namespace Database\Seeders;

use App\Models\WebsiteLogo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class WebsiteLogoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (WebsiteLogo::query()->exists()) {
            return;
        }

        $sourcePath = base_path('../forms/src/assets/img/logo.png');
        $storedPath = null;

        if (File::exists($sourcePath)) {
            $storedPath = 'website-logo/logo.png';
            Storage::disk('public')->put($storedPath, File::get($sourcePath));
        }

        WebsiteLogo::create([
            'image_path' => $storedPath,
            'main_text' => 'Kalinga ng Kababaihan',
            'secondary_text' => "Women's League Las Pinas",
        ]);
    }
}
