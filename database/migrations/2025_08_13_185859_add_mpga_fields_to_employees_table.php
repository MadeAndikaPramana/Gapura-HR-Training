<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * SAFE VERSION: Only add what doesn't exist yet
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Only add department if it doesn't exist
            if (!Schema::hasColumn('employees', 'department')) {
                $table->string('department', 50)->nullable();
            }

            // Only add unit_organisasi if it doesn't exist
            if (!Schema::hasColumn('employees', 'unit_organisasi')) {
                $table->string('unit_organisasi', 100)->nullable();
            }

            // Only add status if it doesn't exist
            if (!Schema::hasColumn('employees', 'status')) {
                $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            }
        });

        // Add indexes only if they don't exist
        $this->addIndexSafely('employees', 'department', 'idx_employees_department');
        $this->addIndexSafely('employees', 'status', 'idx_employees_status');
        $this->addCompositeIndexSafely('employees', ['department', 'status'], 'idx_employees_dept_status');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes safely
        $this->dropIndexSafely('employees', 'idx_employees_department');
        $this->dropIndexSafely('employees', 'idx_employees_status');
        $this->dropIndexSafely('employees', 'idx_employees_dept_status');

        Schema::table('employees', function (Blueprint $table) {
            // Only drop columns that we added
            $columnsToRemove = [];

            if (Schema::hasColumn('employees', 'department')) {
                $columnsToRemove[] = 'department';
            }

            if (Schema::hasColumn('employees', 'unit_organisasi')) {
                $columnsToRemove[] = 'unit_organisasi';
            }

            if (Schema::hasColumn('employees', 'status')) {
                $columnsToRemove[] = 'status';
            }

            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }

    /**
     * Safely add index if it doesn't exist
     */
    private function addIndexSafely($table, $column, $indexName)
    {
        try {
            if (Schema::hasColumn($table, $column)) {
                // Check if index already exists
                $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
                    ->pluck('Key_name')
                    ->toArray();

                if (!in_array($indexName, $indexes)) {
                    Schema::table($table, function (Blueprint $t) use ($column, $indexName) {
                        $t->index($column, $indexName);
                    });
                }
            }
        } catch (\Exception $e) {
            // Index might already exist with different name, skip
        }
    }

    /**
     * Safely add composite index if it doesn't exist
     */
    private function addCompositeIndexSafely($table, $columns, $indexName)
    {
        try {
            // Check all columns exist
            $allExist = true;
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    $allExist = false;
                    break;
                }
            }

            if ($allExist) {
                // Check if index already exists
                $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
                    ->pluck('Key_name')
                    ->toArray();

                if (!in_array($indexName, $indexes)) {
                    Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                        $t->index($columns, $indexName);
                    });
                }
            }
        } catch (\Exception $e) {
            // Index might already exist, skip
        }
    }

    /**
     * Safely drop index if it exists
     */
    private function dropIndexSafely($table, $indexName)
    {
        try {
            $indexes = collect(DB::select("SHOW INDEX FROM {$table}"))
                ->pluck('Key_name')
                ->toArray();

            if (in_array($indexName, $indexes)) {
                Schema::table($table, function (Blueprint $t) use ($indexName) {
                    $t->dropIndex($indexName);
                });
            }
        } catch (\Exception $e) {
            // Index might not exist, skip
        }
    }
};
