<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('laboratory', function (Blueprint $table) {
        $table->unsignedBigInteger('custodianID')->nullable()->unique()->change(); // Add unique constraint
    });
}

public function down()
{
    Schema::table('laboratory', function (Blueprint $table) {
        $table->dropUnique(['custodianID']); // Drop the unique constraint
    });
}
};
