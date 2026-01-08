<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('goods_donations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // optional
            $table->string('email')->nullable(); // to email donor
            $table->json('type')->nullable(); // goods donation type(s)
            $table->longText('description')->nullable();
            $table->string('quantity')->nullable();
            $table->string('address');
            $table->year('year');
            $table->string('month'); // as text like "June", "July"
            $table->string("status")->default("pending");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_donations');
    }
};
