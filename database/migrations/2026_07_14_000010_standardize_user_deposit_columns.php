<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'status')) {
                $table->integer('status')->default(1)->after('type_admin');
            }

            if (!Schema::hasColumn('users', 'activated')) {
                $table->boolean('activated')->default(true)->after('status');
            }

            if (!Schema::hasColumn('users', 'active')) {
                $table->boolean('active')->default(true)->after('activated');
            }

            if (!Schema::hasColumn('users', 'role')) {
                $table->integer('role')->default(0)->after('active');
            }

            if (!Schema::hasColumn('users', 'balance')) {
                $table->integer('balance')->default(0)->after('role');
            }

            if (!Schema::hasColumn('users', 'tongnap')) {
                $table->integer('tongnap')->default(0)->after('balance');
            }

            if (!Schema::hasColumn('users', 'tongNapThang')) {
                $table->integer('tongNapThang')->default(0)->after('tongnap');
            }

            if (!Schema::hasColumn('users', 'tongNapTuan')) {
                $table->integer('tongNapTuan')->default(0)->after('tongNapThang');
            }

            if (!Schema::hasColumn('users', 'tongNapThangResetAt')) {
                $table->dateTime('tongNapThangResetAt')->nullable()->after('tongNapThang');
            }

            if (!Schema::hasColumn('users', 'tongNapTuanResetAt')) {
                $table->dateTime('tongNapTuanResetAt')->nullable()->after('tongNapTuan');
            }
        });

        $this->copyLegacyValues();

        Schema::table('users', function (Blueprint $table): void {
            foreach (['money', 'totalmoney', 'tongnapthang', 'tongnapthang_reset_at', 'quanew'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function down(): void
    {
        //
    }

    private function copyLegacyValues(): void
    {
        $updates = [];

        if (Schema::hasColumn('users', 'ban')) {
            $updates['status'] = DB::raw('case when COALESCE(ban, 0) = 1 then 2 else COALESCE(status, 1) end');
        }

        if (Schema::hasColumn('users', 'is_active')) {
            $updates['active'] = DB::raw('COALESCE(is_active, active, 1)');
            $updates['activated'] = DB::raw('COALESCE(is_active, activated, 1)');
        }

        if (Schema::hasColumn('users', 'type_admin')) {
            $updates['role'] = DB::raw('case when COALESCE(type_admin, 0) > 1 then 99 when COALESCE(type_admin, 0) = 1 then 1 else COALESCE(role, 0) end');
        }

        if (Schema::hasColumn('users', 'money')) {
            $updates['balance'] = DB::raw('GREATEST(COALESCE(balance, 0), COALESCE(money, 0))');
        }

        if (Schema::hasColumn('users', 'totalmoney')) {
            $updates['tongnap'] = DB::raw('GREATEST(COALESCE(tongnap, 0), COALESCE(totalmoney, 0))');
        }

        if (Schema::hasColumn('users', 'tongnapthang')) {
            $updates['tongNapThang'] = DB::raw('GREATEST(COALESCE(tongNapThang, 0), COALESCE(tongnapthang, 0))');
        }

        if (Schema::hasColumn('users', 'tongnapthang_reset_at')) {
            $updates['tongNapThangResetAt'] = DB::raw('COALESCE(tongNapThangResetAt, tongnapthang_reset_at)');
        }

        if ($updates !== []) {
            DB::table('users')->update($updates);
        }
    }
};
