<?php

namespace Database\Seeders;

use App\Models\ContactInfo;
use Illuminate\Database\Seeder;

class ContactInforSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ContactInfo::create([
            'telephone_number' => '0283742811',
            'phone_number' => '09209859508',
            'email_address' => 'kalingangkababaihan.wllpc@gmail.com',
            'physical_address' => 'B4 LOT6-6 Fantacy Road 3 Teresa Park Subd., Pilar, Las Piñas City'
        ]);
    }
}
