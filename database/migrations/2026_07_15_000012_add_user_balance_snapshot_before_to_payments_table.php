<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (!Schema::hasColumn('payments', 'user_balance_snapshot_before')) {
                $table->json('user_balance_snapshot_before')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            if (Schema::hasColumn('payments', 'user_balance_snapshot_before')) {
                $table->dropColumn('user_balance_snapshot_before');
            }
        });
    }
};
