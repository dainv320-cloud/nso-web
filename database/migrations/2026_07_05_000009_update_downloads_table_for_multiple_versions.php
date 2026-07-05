<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('downloads', function (Blueprint $table): void {
            if (!Schema::hasColumn('downloads', 'file_name')) {
                $table->string('file_name', 255)->nullable()->after('platform');
            }
        });

        try {
            DB::statement('ALTER TABLE downloads DROP INDEX downloads_platform_unique');
        } catch (\Throwable) {
        }

        try {
            DB::statement('ALTER TABLE downloads DROP CONSTRAINT downloads_platform_unique');
        } catch (\Throwable) {
        }

        try {
            DB::statement('ALTER TABLE downloads DROP CONSTRAINT downloads_platform_key');
        } catch (\Throwable) {
        }
    }

    public function down(): void
    {
        try {
            DB::statement('ALTER TABLE downloads ADD CONSTRAINT downloads_platform_unique UNIQUE (platform)');
        } catch (\Throwable) {
        }

        Schema::table('downloads', function (Blueprint $table): void {
            if (Schema::hasColumn('downloads', 'file_name')) {
                $table->dropColumn('file_name');
            }
        });
    }
};
