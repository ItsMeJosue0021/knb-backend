<?php

use App\Models\Expenditure;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('expenditure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Expenditure::class)->constrained()->onDelete('cascade');
            $table->string('name');
            $table->longText('description')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('unit_price', 10, 2);
            $table->string('image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenditure_items');
    }
};
