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
            if (!Schema::hasColumn('users', 'tongnapthang_reset_at')) {
                $table->timestamp('tongnapthang_reset_at')->nullable()->after('tongnapthang');
            }
        });

        DB::table('users')->whereNull('tongnapthang_reset_at')->update([
            'tongnapthang_reset_at' => now(),
        ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'tongnapthang_reset_at')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('tongnapthang_reset_at');
        });
    }
};
