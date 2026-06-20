<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_feedback')) {
            Schema::create('user_feedback', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('type', 30);
                $table->string('subject', 180);
                $table->text('content');
                $table->string('status', 30)->default('new');
                $table->timestamps();

                $table->index('user_id');
                $table->index('type');
                $table->index('status');
                $table->index('created_at');
            });
        }

        Schema::table('user_feedback', function (Blueprint $table): void {
            if (!Schema::hasColumn('user_feedback', 'user_id')) {
                $table->unsignedBigInteger('user_id')->after('id');
            }

            if (!Schema::hasColumn('user_feedback', 'type')) {
                $table->string('type', 30)->after('user_id');
            }

            if (!Schema::hasColumn('user_feedback', 'subject')) {
                $table->string('subject', 180)->after('type');
            }

            if (!Schema::hasColumn('user_feedback', 'content')) {
                $table->text('content')->after('subject');
            }

            if (!Schema::hasColumn('user_feedback', 'status')) {
                $table->string('status', 30)->default('new')->after('content');
            }

            if (!Schema::hasColumn('user_feedback', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('user_feedback', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_feedback');
    }
};
