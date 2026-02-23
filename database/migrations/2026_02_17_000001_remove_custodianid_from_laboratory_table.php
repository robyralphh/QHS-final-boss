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
        Schema::table('laboratory', function (Blueprint $table) {
            // Drop the unique constraint if it exists
            if (Schema::hasColumn('laboratory', 'custodianID')) {
                $table->dropUnique(['custodianID']);
                $table->dropColumn('custodianID');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory', function (Blueprint $table) {
            $table->unsignedBigInteger('custodianID')->nullable();
        });
    }
};
