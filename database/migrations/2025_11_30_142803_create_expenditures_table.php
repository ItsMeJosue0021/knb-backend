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
        Schema::create('expenditures', function (Blueprint $table) {
            $table->id();
            $table->string('reference_number')->unique();
            $table->string('name');
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->date('date_incurred');
            $table->string('payment_method');
            $table->string('notes')->nullable();
            $table->string('status')->default('pending');
            $table->string('attachment');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenditures');
    }
};
