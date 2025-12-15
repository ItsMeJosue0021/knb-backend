<?php

use App\Models\User;
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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('member_number')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('nick_name')->nullable();
            $table->text('address');
            $table->date('dob');
            $table->string('civil_status');
            $table->string('contact_number');
            $table->string('fb_messenger_account')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])
            ->default('pending');

            $table->foreignIdFor(User::class)
                ->nullable()
                ->unique()
                ->constrained()
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
