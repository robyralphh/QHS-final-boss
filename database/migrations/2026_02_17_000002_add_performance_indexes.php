<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add indexes for dashboard queries (skip if they already exist)
        Schema::table('equipment_items', function (Blueprint $table) {
            if (!$this->indexExists('equipment_items', 'equipment_items_equipment_id_index')) {
                $table->index('equipment_id');
            }
            if (!$this->indexExists('equipment_items', 'equipment_items_isborrowed_index')) {
                $table->index('isBorrowed');
            }
            if (!$this->indexExists('equipment_items', 'equipment_items_condition_index')) {
                $table->index('condition');
            }
            if (!$this->indexExists('equipment_items', 'equipment_items_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('equipment', function (Blueprint $table) {
            if (!$this->indexExists('equipment', 'equipment_laboratory_id_index')) {
                $table->index('laboratory_id');
            }
            if (!$this->indexExists('equipment', 'equipment_isactive_index')) {
                $table->index('isActive');
            }
            if (!$this->indexExists('equipment', 'equipment_created_at_index')) {
                $table->index('created_at');
            }
        });

        Schema::table('transactions', function (Blueprint $table) {
            if (!$this->indexExists('transactions', 'transactions_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('transactions', 'transactions_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('transactions', 'transactions_laboratory_id_index')) {
                $table->index('laboratory_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (!$this->indexExists('users', 'users_created_at_index')) {
                $table->index('created_at');
            }
            if (!$this->indexExists('users', 'users_isactive_index')) {
                $table->index('isActive');
            }
            if (!$this->indexExists('users', 'users_role_index')) {
                $table->index('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_items', function (Blueprint $table) {
            $table->dropIndexIfExists(['equipment_id']);
            $table->dropIndexIfExists(['isBorrowed']);
            $table->dropIndexIfExists(['condition']);
            $table->dropIndexIfExists(['created_at']);
        });

        Schema::table('equipment', function (Blueprint $table) {
            $table->dropIndexIfExists(['laboratory_id']);
            $table->dropIndexIfExists(['isActive']);
            $table->dropIndexIfExists(['created_at']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndexIfExists(['status']);
            $table->dropIndexIfExists(['created_at']);
            $table->dropIndexIfExists(['laboratory_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndexIfExists(['created_at']);
            $table->dropIndexIfExists(['isActive']);
            $table->dropIndexIfExists(['role']);
        });
    }

    private function indexExists($table, $indexName)
    {
        return collect(\DB::select("SHOW INDEXES FROM {$table}"))->contains(function ($index) use ($indexName) {
            return $index->Key_name === $indexName;
        });
    }
};
