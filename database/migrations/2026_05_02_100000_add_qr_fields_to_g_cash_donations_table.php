<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('g_cash_donations', function (Blueprint $table) {
            $table->string('payment_channel')->default('gateway')->after('amount');
            $table->string('payment_reference_number')->nullable()->after('payment_channel');
            $table->string('proof_of_payment')->nullable()->after('payment_reference_number');
            $table->timestamp('confirmed_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('g_cash_donations', function (Blueprint $table) {
            $table->dropColumn([
                'payment_channel',
                'payment_reference_number',
                'proof_of_payment',
                'confirmed_at',
            ]);
        });
    }
};
