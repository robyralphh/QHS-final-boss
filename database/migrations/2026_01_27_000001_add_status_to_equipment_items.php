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
        });
    }
};
