<?php

namespace Database\Seeders;

use App\Models\Involvement;
use Illuminate\Database\Seeder;

class InvolvementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Involvement::create([
            'title' => 'Get Involved',
            'description' => 'Ways to participate in our mission',
            'involvements' => [
                [
                    'id' => 1,
                    'title' => 'Volunteer',
                    'description' => 'Join our team of volunteers',
                    'action' => 'Volunteer now',
                    'url' => '/volunteer',
                ],
                [
                    'id' => 2,
                    'title' => 'Donate',
                    'description' => 'Support our cause with a donation',
                    'action' => 'Donate now',
                    'url' => '/donate',
                ],
                [
                    'id' => 3,
                    'title' => 'Partner',
                    'description' => 'Collaborate with us on projects',
                    'action' => 'Partner with us',
                    'url' => '/partner',
                ],
            ],
        ]);
    }
}
