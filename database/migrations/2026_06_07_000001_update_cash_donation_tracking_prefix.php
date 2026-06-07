<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('cash_donations')
            ->where('donation_tracking_number', 'like', 'GDN-%')
            ->update([
                'donation_tracking_number' => DB::raw("CONCAT('CDN-', SUBSTRING(donation_tracking_number, 5))"),
            ]);
    }

    public function down(): void
    {
        DB::table('cash_donations')
            ->where('donation_tracking_number', 'like', 'CDN-%')
            ->update([
                'donation_tracking_number' => DB::raw("CONCAT('GDN-', SUBSTRING(donation_tracking_number, 5))"),
            ]);
    }
};
