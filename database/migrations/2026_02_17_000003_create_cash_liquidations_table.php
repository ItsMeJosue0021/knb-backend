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
        Schema::create('cash_liquidations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('date_used');
            $table->date('used_at');
            $table->date('date');
            $table->string('point_person');
            $table->string('person_responsible');
            $table->string('receipt')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'date_used']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_liquidations');
    }
};
