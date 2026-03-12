<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('homepage_infos', function (Blueprint $table) {
            $table->string('primary_button_text')->nullable();
            $table->string('primary_button_url')->nullable();
            $table->string('secondary_button_text')->nullable();
            $table->string('secondary_button_url')->nullable();
            $table->string('women_supported_label')->nullable();
            $table->string('meals_served_label')->nullable();
            $table->string('communities_reached_label')->nullable();
            $table->string('number_of_volunteers_label')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('homepage_infos', function (Blueprint $table) {
            $table->dropColumn([
                'primary_button_text',
                'primary_button_url',
                'secondary_button_text',
                'secondary_button_url',
                'women_supported_label',
                'meals_served_label',
                'communities_reached_label',
                'number_of_volunteers_label',
            ]);
        });
    }
};
