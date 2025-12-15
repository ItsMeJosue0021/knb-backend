<?php

use App\Models\Project;
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
        Schema::create('volunteer_requests', function (Blueprint $table) {
            $table->id();

            // Volunteer info snapshot
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email');
            $table->string('contact_number');

            // Relations
            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('member_number')->nullable();

            // Flags (optional but useful)
            $table->boolean('is_user')->default(false);
            $table->boolean('is_member')->default(false);

            // Status
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])
                ->default('pending');

            $table->timestamps();

            // Optional: prevent duplicate volunteering per project
            $table->unique(['email', 'project_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('volunteer_requests');
    }
};
