<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'tongNapThangResetAt')) {
                $table->dateTime('tongNapThangResetAt')->nullable()->after('tongNapThang');
            }

            if (!Schema::hasColumn('users', 'tongNapTuanResetAt')) {
                $table->dateTime('tongNapTuanResetAt')->nullable()->after('tongNapTuan');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'tongNapThangResetAt')) {
                $table->dropColumn('tongNapThangResetAt');
            }

            if (Schema::hasColumn('users', 'tongNapTuanResetAt')) {
                $table->dropColumn('tongNapTuanResetAt');
            }
        });
    }
};
