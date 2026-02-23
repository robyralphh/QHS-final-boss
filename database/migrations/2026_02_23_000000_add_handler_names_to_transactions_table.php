<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('accepted_by_name')->nullable();
            $table->string('returned_by_name')->nullable();
            $table->string('rejected_by_name')->nullable();
            $table->text('rejection_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['accepted_by_name', 'returned_by_name', 'rejected_by_name', 'rejection_reason']);
        });
    }
};
