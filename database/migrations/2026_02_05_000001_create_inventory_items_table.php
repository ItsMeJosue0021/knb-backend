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
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('g_d_categories')->cascadeOnDelete();
            $table->foreignId('sub_category_id')->constrained('g_d_subcategories')->cascadeOnDelete();
            $table->string('unit')->default('');
            $table->unsignedInteger('quantity')->default(0);
            $table->timestamps();

            $table->unique(['sub_category_id', 'unit']);
            $table->index(['category_id', 'sub_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};

