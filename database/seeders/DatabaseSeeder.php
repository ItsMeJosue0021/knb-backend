<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Enquiry;
use App\Models\Knowledgebase;
use Illuminate\Database\Seeder;
use Database\Seeders\EventsSeeder;
use Database\Seeders\EnquirySeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\GDCategorySeeder;
use Database\Seeders\KnowledgebaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            MemberSeeder::class,
            EmergencyContactSeeder::class,
            KnowledgebaseSeeder::class,
            EnquirySeeder::class,
            ProjectSeeder::class,
            EventsSeeder::class,
            GDCategorySeeder::class,
        ]);
    }
}
