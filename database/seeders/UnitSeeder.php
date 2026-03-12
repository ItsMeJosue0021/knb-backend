<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            ['unit' => 'kg', 'description' => 'Kilogram (weight)'],
            ['unit' => 'g', 'description' => 'Gram (weight)'],
            ['unit' => 'mg', 'description' => 'Milligram (weight)'],
            ['unit' => 'lb', 'description' => 'Pound (weight)'],
            ['unit' => 'oz', 'description' => 'Ounce (weight)'],
            ['unit' => 'L', 'description' => 'Liter (liquid volume)'],
            ['unit' => 'mL', 'description' => 'Milliliter (liquid volume)'],
            ['unit' => 'gal', 'description' => 'Gallon (liquid volume)'],
            ['unit' => 'pc', 'description' => 'Piece (single item)'],
            ['unit' => 'pcs', 'description' => 'Pieces (multiple items)'],
            ['unit' => 'pack', 'description' => 'Pack (group of items in one package)'],
            ['unit' => 'box', 'description' => 'Box (items grouped inside a box)'],
            ['unit' => 'dozen', 'description' => 'Dozen (12 items)'],
            ['unit' => 'sachet', 'description' => 'Sachet (small sealed packet)'],
            ['unit' => 'can', 'description' => 'Can (canned item)'],
            ['unit' => 'bottle', 'description' => 'Bottle (liquid in a bottle)'],
            ['unit' => 'tray', 'description' => 'Tray (tray-packed items, such as eggs)'],
            ['unit' => 'bar', 'description' => 'Bar (soap bar, chocolate bar)'],
        ];

        foreach ($units as $data) {
            Unit::query()->updateOrCreate(
                ['unit' => $data['unit']],
                ['description' => $data['description']]
            );
        }
    }
}
