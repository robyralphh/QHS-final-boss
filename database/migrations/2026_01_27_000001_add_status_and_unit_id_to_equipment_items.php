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
        Schema::table('equipment_items', function (Blueprint $table) {
            // Add unit_id if it doesn't exist
            if (!Schema::hasColumn('equipment_items', 'unit_id')) {
                $table->string('unit_id')->nullable()->unique()->after('equipment_id');
            }
            
            // Add status if it doesn't exist
            if (!Schema::hasColumn('equipment_items', 'status')) {
                $table->enum('status', ['available', 'damaged', 'missing', 'under_repair'])
                    ->default('available')
                    ->after('unit_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('equipment_items', function (Blueprint $table) {
            if (Schema::hasColumn('equipment_items', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('equipment_items', 'unit_id')) {
                $table->dropUnique(['unit_id']);
                $table->dropColumn('unit_id');
            }
        });
    }
};
