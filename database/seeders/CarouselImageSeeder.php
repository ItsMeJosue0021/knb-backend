<?php

namespace Database\Seeders;

use App\Models\CarouselImage;
use Illuminate\Database\Seeder;

class CarouselImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $images = [
            'carousel_images/sample-1.jpg',
            'carousel_images/sample-2.jpg',
            'carousel_images/sample-3.jpg',
        ];

        foreach ($images as $path) {
            CarouselImage::create([
                'image_path' => $path,
            ]);
        }
    }
}
