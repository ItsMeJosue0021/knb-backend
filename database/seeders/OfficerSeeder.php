<?php

namespace Database\Seeders;

use App\Models\Officers;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class OfficerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $officers = [
            ['name' => 'Beavin Soriano', 'position' => 'President'],
            ['name' => 'Juliet Eronico', 'position' => 'Vice President'],
            ['name' => 'Cherry Balili', 'position' => 'Secretary'],
            ['name' => 'Gina Losare', 'position' => 'Treasurer'],
            ['name' => 'Marieatha Lim', 'position' => 'Auditor'],
        ];

        foreach ($officers as $officer) {
            Officers::create($officer);
        }
    }
}


