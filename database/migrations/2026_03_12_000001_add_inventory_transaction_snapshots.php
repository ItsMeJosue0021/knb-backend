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
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->string('snapshot_name')->nullable()->after('source_name');
            $table->foreignId('snapshot_category_id')->nullable()->after('snapshot_name')->constrained('g_d_categories')->nullOnDelete();
            $table->foreignId('snapshot_sub_category_id')->nullable()->after('snapshot_category_id')->constrained('g_d_subcategories')->nullOnDelete();
            $table->string('snapshot_unit')->default('')->after('snapshot_sub_category_id');

            $table->index('snapshot_name', 'inventory_transactions_snapshot_name_idx');
            $table->index(
                ['snapshot_category_id', 'snapshot_sub_category_id'],
                'inventory_transactions_snapshot_category_idx'
            );
            $table->index(
                ['inventory_item_id', 'type', 'occurred_at'],
                'inventory_transactions_item_type_time_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_transactions', function (Blueprint $table) {
            $table->dropIndex('inventory_transactions_snapshot_name_idx');
            $table->dropIndex('inventory_transactions_snapshot_category_idx');
            $table->dropIndex('inventory_transactions_item_type_time_idx');

            $table->dropForeign(['snapshot_category_id']);
            $table->dropForeign(['snapshot_sub_category_id']);

            $table->dropColumn([
                'snapshot_name',
                'snapshot_category_id',
                'snapshot_sub_category_id',
                'snapshot_unit',
            ]);
        });
    }
};
