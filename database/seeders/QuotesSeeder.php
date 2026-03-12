<?php

namespace Database\Seeders;

use App\Models\Quotes;
use Illuminate\Database\Seeder;

class QuotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Quotes::create([
            'title' => 'Words of Hope',
            'description' => 'Messages that lift us up.',
            'quotes' => [
                [
                    'quote' => 'Hope is stronger than fear.',
                    'author' => 'Kalinga Volunteer',
                ],
                [
                    'quote' => 'Small acts create big change.',
                    'author' => 'Community Partner',
                ],
                [
                    'quote' => 'Together, we rise.',
                    'author' => 'Supporter',
                ],
            ],
        ]);
    }
}
