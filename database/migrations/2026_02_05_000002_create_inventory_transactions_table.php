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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->foreignId('goods_donation_id')->nullable()->constrained('goods_donations')->nullOnDelete();
            $table->foreignId('source_item_id')->nullable()->constrained('items')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->enum('type', ['in', 'out', 'adjustment']);
            $table->unsignedInteger('quantity');
            $table->dateTime('occurred_at');
            $table->string('source_name')->nullable();
            $table->string('unit')->default('');
            $table->longText('notes')->nullable();
            $table->timestamps();

            $table->index(['type', 'occurred_at']);
            $table->index('source_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};

