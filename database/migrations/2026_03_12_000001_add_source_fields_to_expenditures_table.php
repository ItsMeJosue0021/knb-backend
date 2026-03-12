<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenditures', function (Blueprint $table) {
            $table->string('source_type')->default('manual')->after('attachment');
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            $table->foreignId('project_id')->nullable()->after('source_id')->constrained('projects')->nullOnDelete();
            $table->string('attachment')->nullable()->change();
        });

        $cashLiquidations = DB::table('cash_liquidations')
            ->leftJoin('projects', 'projects.id', '=', 'cash_liquidations.project_id')
            ->select(
                'cash_liquidations.*',
                'projects.title as project_title'
            )
            ->orderBy('cash_liquidations.id')
            ->get();

        foreach ($cashLiquidations as $cashLiquidation) {
            $projectTitle = $cashLiquidation->project_title ?: 'Untitled Project';
            $pointPerson = trim((string) $cashLiquidation->point_person);

            DB::table('expenditures')->insert([
                'reference_number' => 'EXP-LIQ-' . $cashLiquidation->id,
                'name' => 'Project Cash Liquidation',
                'description' => 'Auto-generated from project liquidation for ' . $projectTitle . '.',
                'amount' => $cashLiquidation->amount,
                'date_incurred' => $cashLiquidation->date_used,
                'payment_method' => 'Project Liquidation',
                'notes' => $pointPerson !== ''
                    ? 'Recorded from project cash liquidation. Point person: ' . $pointPerson . '.'
                    : 'Recorded from project cash liquidation.',
                'status' => 'recorded',
                'attachment' => $cashLiquidation->receipt,
                'source_type' => 'project_liquidation',
                'source_id' => $cashLiquidation->id,
                'project_id' => $cashLiquidation->project_id,
                'created_at' => $cashLiquidation->created_at,
                'updated_at' => $cashLiquidation->updated_at,
            ]);
        }

        Schema::table('expenditures', function (Blueprint $table) {
            $table->index('source_type');
            $table->index('source_id');
            $table->unique(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('expenditures')
            ->where('source_type', 'project_liquidation')
            ->delete();

        Schema::table('expenditures', function (Blueprint $table) {
            $table->dropUnique(['source_type', 'source_id']);
            $table->dropIndex(['source_type']);
            $table->dropIndex(['source_id']);
            $table->dropConstrainedForeignId('project_id');
            $table->dropColumn(['source_type', 'source_id']);
            $table->string('attachment')->nullable(false)->change();
        });
    }
};
