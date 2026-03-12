<?php

namespace Database\Seeders;

use App\Models\ItemName;
use Illuminate\Database\Seeder;

class ItemNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = [
            'Noodles',
            'Lucky me Beef',
            'Lucky me Chicken',
            'Pasta',
            'Mega Sardines',
            '555 Tuna',
            'San marino',
            'Rice',
            'Bottled water',
            '3in1 Coffee',
            'Berbrand',
            'Milo',
            'Fudge Bar',
            'Rebisco Chocolate',
            'Rebisco Crackers',
            'Rebisco Hansel crackers',
            'Monde Mamon',
            'Young town Sardines',
            'Century Tuna',
        ];

        foreach ($names as $name) {
            ItemName::updateOrCreate(
                ['name' => $name],
                ['name' => $name]
            );
        }
    }
}
