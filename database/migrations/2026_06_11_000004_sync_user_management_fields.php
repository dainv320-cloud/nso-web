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

        DB::table('users')->whereNull('name')->update([
            'name' => DB::raw('username'),
        ]);

        DB::table('users')->whereNull('created_at')->update([
            'created_at' => now(),
        ]);

        DB::table('users')->whereNull('updated_at')->update([
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        //
    }
};
