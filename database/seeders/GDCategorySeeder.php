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
                // Staples
                'Rice',
                'Noodles & Pasta',

                // Ready-to-eat / Easy to prepare
                'Canned Goods (Sardines, Corned Beef, Meat Loaf)',
                'Instant Noodles',
                'Instant Meals & Ready-to-Eat Food',

                // Protein
                'Canned Fish & Meat',

                // Beverages
                'Coffee',
                'Powdered Drinks',
                'Juice & Canned Drinks',

                // Others
                'Snacks & Biscuits',
                'Baby Food & Formula'
            ],

            'Clothings' => [
                // Adult Clothing
                'Men – Tops',
                'Men – Bottoms',
                'Women – Tops',
                'Women – Bottoms',

                // Children
                'Children – Infants (0–2 yrs)',
                'Children – Toddlers (3–5 yrs)',
                'Children – Kids (6–12 yrs)',

                // Special Use
                'School Uniforms',
                'Jackets & Sweaters',
                'Sleepwear',

                // Footwear & Accessories
                'Footwear',
                'Undergarments',
                'Bags & Belts',

                // Household Textile
                'Bedding (Blankets, Bedsheets)',
                'Towels'
            ],

            'School Supplies' => [
                // Writing & Paper
                'Notebooks',
                'Writing Tools (Pens, Pencils)',
                'Paper Products (Bond Paper, Pad Paper)',

                // School Gear
                'School Bags',
                'Lunch Boxes',

                // Art & Learning
                'Art Materials (Crayons, Coloring Materials)',
                'Learning Materials (Books, Workbooks)',

                // Miscellaneous
                'School Kits (Pre-packed Sets)',
                'Teaching Supplies'
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
