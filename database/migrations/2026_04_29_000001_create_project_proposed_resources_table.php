<?php

use App\Models\GDCategory;
use App\Models\GDSubcategory;
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
        Schema::create('project_proposed_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Project::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->foreignIdFor(GDCategory::class, 'category_id')->nullable()->constrained('g_d_categories')->nullOnDelete();
            $table->foreignIdFor(GDSubcategory::class, 'sub_category_id')->nullable()->constrained('g_d_subcategories')->nullOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('unit', 50)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'display_order'], 'project_proposed_resources_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_proposed_resources');
    }
};
