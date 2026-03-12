<?php

namespace Database\Seeders;

use App\Models\Encouragement;
use Illuminate\Database\Seeder;

class EncouragementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Encouragement::create([
            'title' => 'You are not alone',
            'description' => 'We are here to support you.',
            'checklist' => [
                ['item' => 'Access to safe spaces'],
                ['item' => 'Supportive community'],
                ['item' => 'Basic necessities'],
            ],
            'image_path' => 'encouragement/sample.jpg',
        ]);
    }
}
