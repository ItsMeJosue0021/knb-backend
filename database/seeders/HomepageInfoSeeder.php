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
            'primary_button_text' => 'Donate Now',
            'primary_button_url' => '/donate',
            'secondary_button_text' => 'Talk to Us',
            'secondary_button_url' => '/contact-us',
            'women_supported' => '500+',
            'women_supported_label' => 'Women supported',
            'meals_served' => '10,000+',
            'meals_served_label' => 'Meals served',
            'communities_reached' => '15',
            'communities_reached_label' => 'Communities reached',
            'number_of_volunteers' => '200+',
            'number_of_volunteers_label' => 'Number of volunteers',
        ]);
    }
}
