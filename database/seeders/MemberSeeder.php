<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Member::truncate();

        Member::create([
            'member_number' => 'MEM-0001',
            'first_name' => 'John',
            'middle_name' => 'A.',
            'last_name' => 'Doe',
            'nick_name' => 'Johnny',
            'address' => '123 Main St, Anytown, USA',
            'dob' => '1990-01-01',
            'civil_status' => 'Single',
            'contact_number' => '123-456-7890',
            'fb_messenger_account' => 'john.doe'
        ]);

        Member::create([
            'member_number' => 'MEM-0002',
            'first_name' => 'Jane',
            'middle_name' => 'B.',
            'last_name' => 'Smith',
            'nick_name' => 'Janey',
            'address' => '456 Elm St, Othertown, USA',
            'dob' => '1985-05-15',
            'civil_status' => 'Married',
            'contact_number' => '234-567-8901',
            'fb_messenger_account' => 'jane.smith'
        ]);

        Member::create([
            'member_number' => 'MEM-0003',
            'first_name' => 'Alice',
            'middle_name' => 'C.',
            'last_name' => 'Johnson',
            'nick_name' => 'Ali',
            'address' => '789 Oak St, Thistown, USA',
            'dob' => '1992-10-10',
            'civil_status' => 'Single',
            'contact_number' => '345-678-9012',
            'fb_messenger_account' => 'alice.johnson'
        ]);

        Member::create([
            'member_number' => 'MEM-0004',
            'first_name' => 'Bob',
            'middle_name' => 'D.',
            'last_name' => 'Williams',
            'nick_name' => 'Bobby',
            'address' => '321 Pine St, Thatown, USA',
            'dob' => '1988-03-25',
            'civil_status' => 'Divorced',
            'contact_number' => '456-789-0123',
            'fb_messenger_account' => 'bob.williams'
        ]);

        Member::create([
            'member_number' => 'MEM-0005',
            'first_name' => 'Charlie',
            'middle_name' => 'E.',
            'last_name' => 'Brown',
            'nick_name' => 'Charlie',
            'address' => '654 Maple St, Hometown, USA',
            'dob' => '1995-07-20',
            'civil_status' => 'Single',
            'contact_number' => '567-890-1234',
            'fb_messenger_account' => 'charlie.brown'
        ]);
    }
}
