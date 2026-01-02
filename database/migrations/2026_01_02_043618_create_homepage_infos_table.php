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
        Schema::create('homepage_infos', function (Blueprint $table) {
            $table->id();
            $table->text('welcome_message');
            $table->text('intro_text');
            $table->string('women_supported')->nullable();
            $table->string('meals_served')->nullable();
            $table->string('communities_reached')->nullable();
            $table->string('number_of_volunteers')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homepage_infos');
    }
};
