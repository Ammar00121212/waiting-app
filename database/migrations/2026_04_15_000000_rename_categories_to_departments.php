<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Rename table categories -> departments (no data loss).
        if (Schema::hasTable('categories') && ! Schema::hasTable('departments')) {
            Schema::rename('categories', 'departments');
        }

        // 2) doctors.category_id -> doctors.department_id (preserve data + FK).
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'category_id') && ! Schema::hasColumn('doctors', 'department_id')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('id');
            });

            DB::table('doctors')->update(['department_id' => DB::raw('category_id')]);

            Schema::table('doctors', function (Blueprint $table) {
                // Drop the old constrained FK + column if present.
                try {
                    $table->dropConstrainedForeignId('category_id');
                } catch (\Throwable $e) {
                    // Best-effort: older schemas or custom FK names.
                    if (Schema::hasColumn('doctors', 'category_id')) {
                        try {
                            $table->dropColumn('category_id');
                        } catch (\Throwable $e2) {
                            // ignore
                        }
                    }
                }

                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->cascadeOnDelete();
            });
        }

        // 3) patients.category_id -> patients.department_id (preserve data + FK).
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'category_id') && ! Schema::hasColumn('patients', 'department_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('phone');
            });

            DB::table('patients')->update(['department_id' => DB::raw('category_id')]);

            Schema::table('patients', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('category_id');
                } catch (\Throwable $e) {
                    if (Schema::hasColumn('patients', 'category_id')) {
                        try {
                            $table->dropColumn('category_id');
                        } catch (\Throwable $e2) {
                            // ignore
                        }
                    }
                }

                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->nullOnDelete();
            });
        }

        // 4) users.category_id -> users.department_id (preserve data + FK).
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'category_id') && ! Schema::hasColumn('users', 'department_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('department_id')->nullable()->after('id');
            });

            DB::table('users')->update(['department_id' => DB::raw('category_id')]);

            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('category_id');
                } catch (\Throwable $e) {
                    if (Schema::hasColumn('users', 'category_id')) {
                        try {
                            $table->dropColumn('category_id');
                        } catch (\Throwable $e2) {
                            // ignore
                        }
                    }
                }

                $table->foreign('department_id')
                    ->references('id')
                    ->on('departments')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // Reverse: departments -> categories.
        if (Schema::hasTable('departments') && ! Schema::hasTable('categories')) {
            Schema::rename('departments', 'categories');
        }

        // doctors.department_id -> doctors.category_id
        if (Schema::hasTable('doctors') && Schema::hasColumn('doctors', 'department_id') && ! Schema::hasColumn('doctors', 'category_id')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('id');
            });

            DB::table('doctors')->update(['category_id' => DB::raw('department_id')]);

            Schema::table('doctors', function (Blueprint $table) {
                try {
                    $table->dropForeign(['department_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                try {
                    $table->dropColumn('department_id');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->cascadeOnDelete();
            });
        }

        // patients.department_id -> patients.category_id
        if (Schema::hasTable('patients') && Schema::hasColumn('patients', 'department_id') && ! Schema::hasColumn('patients', 'category_id')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('phone');
            });

            DB::table('patients')->update(['category_id' => DB::raw('department_id')]);

            Schema::table('patients', function (Blueprint $table) {
                try {
                    $table->dropForeign(['department_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                try {
                    $table->dropColumn('department_id');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            });
        }

        // users.department_id -> users.category_id
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'department_id') && ! Schema::hasColumn('users', 'category_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('category_id')->nullable()->after('id');
            });

            DB::table('users')->update(['category_id' => DB::raw('department_id')]);

            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropForeign(['department_id']);
                } catch (\Throwable $e) {
                    // ignore
                }
                try {
                    $table->dropColumn('department_id');
                } catch (\Throwable $e) {
                    // ignore
                }

                $table->foreign('category_id')
                    ->references('id')
                    ->on('categories')
                    ->nullOnDelete();
            });
        }
    }
};

