<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\FaqsSeeder;
use Database\Seeders\EventsSeeder;
use Database\Seeders\EnquirySeeder;
use Database\Seeders\OfficerSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\GDCategorySeeder;
use Database\Seeders\ContactInforSeeder;
use Database\Seeders\HomepageInfoSeeder;
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
            ContactInforSeeder::class,
            FaqsSeeder::class,
            OfficerSeeder::class,
            HomepageInfoSeeder::class
        ]);
    }
}
