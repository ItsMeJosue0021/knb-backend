<?php

namespace Database\Seeders;

use App\Models\HomepageInfo;
use Illuminate\Database\Seeder;

class HomepageInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        HomepageInfo::create([
            'welcome_message' => 'Building hope for women, families, and communities',
            'intro_text' => 'Kalinga ng Kababaihan champions dignity through relief, livelihood, and safe spaces. Your support makes every story of resilience possible.',
            'women_supported' => '500+',
            'meals_served' => '10,000+',
            'communities_reached' => '15',
            'number_of_volunteers' => '200+'
        ]);
    }
}
