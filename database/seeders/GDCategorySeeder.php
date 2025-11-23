<?php

namespace Database\Seeders;

use App\Models\GDCategory;
use App\Models\GDSubcategory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class GDCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Food' => [
                'Canned Goods',
                'Instant Noodles',
                'Rice',
                'Beverages',
                'Snacks',
                'Condiments'
            ],

            'Clothes' => [
                'Men',
                'Women',
                'Children',
                'Footwear',
                'Bedding'
            ],

            'School Supplies' => [
                'Paper Products',
                'Writing Tools',
                'Bags',
                'Art Materials'
            ],

            'Hygiene Products' => [
                'Soap',
                'Shampoo',
                'Sanitary Pads',
                'Toothpaste',
                'Toothbrush'
            ],

            'Medicine' => [
                'OTC Medicines',
                'Vitamins',
                'First Aid Items'
            ],
        ];

        foreach ($categories as $categoryName => $subcategories) {
            // Create category
            $category = GDCategory::create([
                'name' => $categoryName,
            ]);

            // Create subcategories
            foreach ($subcategories as $sub) {
                GDSubcategory::create([
                    'g_d_category_id' => $category->id,
                    'name' => $sub,
                ]);
            }
        }
    }
}
