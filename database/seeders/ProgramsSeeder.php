<?php

namespace Database\Seeders;

use App\Models\Programs;
use Illuminate\Database\Seeder;

class ProgramsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Programs::create([
            'title' => 'Programs that create lasting change',
            'description' => 'From immediate relief to long-term empowerment, our programs are designed to meet women and families where they are.',
            'programs' => [
                [
                    'id' => 1,
                    'title' => 'Relief & Care',
                    'description' => 'Food packs, hygiene kits, and safe spaces for women and children affected by crisis.',
                ],
                [
                    'id' => 2,
                    'title' => 'Skills & Livelihood',
                    'description' => 'Workshops and starter support that help women earn and build confidence.',
                ],
                [
                    'id' => 3,
                    'title' => 'Community Building',
                    'description' => 'Partnerships with barangays and volunteers to sustain programs where they are needed most.',
                ],
            ],
        ]);
    }
}
