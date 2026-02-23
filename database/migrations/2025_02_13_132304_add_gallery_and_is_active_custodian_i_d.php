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
            $table->unsignedBigInteger('custodianID')->nullable()->unique(); // Add unique constraint
            $table->string('isActive')->nullable();
            $table->string('gallery')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory', function (Blueprint $table) {
            $table->dropUnique(['custodianID']); // Drop the unique constraint
            $table->dropColumn(['custodianID', 'isActive', 'gallery']);
        });
    }
};