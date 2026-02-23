<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to rename the column
        if (Schema::hasColumn('equipment_items', 'status')) {
            DB::statement('ALTER TABLE equipment_items CHANGE `status` `condition` VARCHAR(255) DEFAULT "available"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Use raw SQL to rename back
        if (Schema::hasColumn('equipment_items', 'condition')) {
            DB::statement('ALTER TABLE equipment_items CHANGE `condition` `status` VARCHAR(255) DEFAULT "available"');
        }
    }
};

