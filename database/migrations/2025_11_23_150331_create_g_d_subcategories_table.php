<?php

use App\Models\GDCategory;
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
        Schema::create('g_d_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(GDCategory::class)->constrained('g_d_categories')->onDelete('cascade');
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('g_d_subcategories');
    }
};
