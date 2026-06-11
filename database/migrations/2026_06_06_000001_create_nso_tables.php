<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table): void {
                $table->id();
                $table->string('name')->nullable();
                $table->string('username', 50)->unique();
                $table->string('email', 180)->nullable()->unique();
                $table->string('password');
                $table->boolean('ban')->default(false);
                $table->boolean('is_active')->default(true);
                $table->smallInteger('type_admin')->default(0);
                $table->integer('money')->default(0);
                $table->integer('totalmoney')->default(0);
                $table->integer('tongnapthang')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            if (!Schema::hasColumn('users', 'name')) {
                $table->string('name')->nullable()->after('id');
            }

            if (!Schema::hasColumn('users', 'email')) {
                $table->string('email', 180)->nullable()->after('username');
            }

            if (!Schema::hasColumn('users', 'ban')) {
                $table->boolean('ban')->default(false)->after('password');
            }

            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('ban');
            }

            if (!Schema::hasColumn('users', 'type_admin')) {
                $table->smallInteger('type_admin')->default(0)->after('is_active');
            }

            if (!Schema::hasColumn('users', 'money')) {
                $table->integer('money')->default(0)->after('type_admin');
            }

            if (!Schema::hasColumn('users', 'totalmoney')) {
                $table->integer('totalmoney')->default(0)->after('money');
            }

            if (!Schema::hasColumn('users', 'tongnapthang')) {
                $table->integer('tongnapthang')->default(0)->after('totalmoney');
            }

            if (!Schema::hasColumn('users', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('tongnapthang');
            }

            if (!Schema::hasColumn('users', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });

        if (!Schema::hasTable('deposit_history')) {
            Schema::create('deposit_history', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('amount', 15, 2);
                $table->string('payment_method', 50);
                $table->string('transaction_code', 100)->nullable();
                $table->string('status', 20)->default('pending');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }

        if (!Schema::hasTable('posts')) {
            Schema::create('posts', function (Blueprint $table): void {
                $table->id();
                $table->text('title');
                $table->string('slug')->unique();
                $table->string('category', 100);
                $table->text('summary');
                $table->longText('content');
                $table->text('image_url')->nullable();
                $table->string('status', 50)->default('published');
                $table->boolean('is_featured')->default(false);
                $table->timestamp('published_at')->useCurrent();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('downloads')) {
            Schema::create('downloads', function (Blueprint $table): void {
                $table->id();
                $table->string('platform', 60)->unique();
                $table->string('version', 40);
                $table->string('file_size', 40);
                $table->string('download_url', 500);
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table): void {
                $table->id();
                $table->string('bank_name', 120);
                $table->string('bank_code', 50);
                $table->string('acc_num', 80);
                $table->string('acc_name', 180);
                $table->string('code', 20);
                $table->decimal('bank_rate', 12, 4)->default(1);
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('bank_account_id')->nullable();
                $table->string('transaction_id', 120)->unique();
                $table->string('bank', 50)->nullable();
                $table->string('type', 20)->default('IN');
                $table->decimal('amount', 15, 2);
                $table->integer('coin_amount')->default(0);
                $table->string('status', 30)->default('success');
                $table->text('description')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('bank_account_id');
            });
        }

        if (!Schema::hasTable('deposits')) {
            Schema::create('deposits', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('payment_id')->nullable();
                $table->decimal('amount', 15, 2);
                $table->integer('coin_amount')->default(0);
                $table->string('payment_method', 50)->default('vietqr');
                $table->string('transaction_code', 120)->nullable();
                $table->string('status', 20)->default('success');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->index('user_id');
                $table->index('payment_id');
            });
        }

        if (!Schema::hasTable('promotion_campaigns')) {
            Schema::create('promotion_campaigns', function (Blueprint $table): void {
                $table->id();
                $table->string('name', 180);
                $table->decimal('bonus_percent', 8, 2)->default(0);
                $table->timestamp('starts_at')->useCurrent();
                $table->timestamp('ends_at')->useCurrent();
                $table->boolean('is_active')->default(true);
                $table->text('note')->nullable();
                $table->timestamps();
            });
        }

        DB::table('users')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'admin',
                'username' => 'admin',
                'email' => 'admin@example.local',
                'password' => Hash::make('123456'),
                'ban' => false,
                'is_active' => true,
                'type_admin' => 1,
                'money' => 0,
                'totalmoney' => 0,
                'tongnapthang' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_campaigns');
        Schema::dropIfExists('deposits');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('downloads');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('deposit_history');
        Schema::dropIfExists('users');
    }
};
