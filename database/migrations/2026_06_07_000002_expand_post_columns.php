<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('alter table posts modify title text not null');
            DB::statement('alter table posts modify slug varchar(255) not null');
            DB::statement('alter table posts modify category varchar(100) not null');
            DB::statement('alter table posts modify summary text not null');
            DB::statement('alter table posts modify content longtext not null');
            DB::statement('alter table posts modify image_url text null');
            DB::statement('alter table posts modify status varchar(50) not null default "published"');

            return;
        }

        DB::statement('alter table posts alter column title type text');
        DB::statement('alter table posts alter column slug type varchar(255)');
        DB::statement('alter table posts alter column category type varchar(100)');
        DB::statement('alter table posts alter column summary type text');
        DB::statement('alter table posts alter column content type text');
        DB::statement('alter table posts alter column image_url type text');
        DB::statement('alter table posts alter column status type varchar(50)');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('alter table posts modify title varchar(180) not null');
            DB::statement('alter table posts modify slug varchar(200) not null');
            DB::statement('alter table posts modify category varchar(50) not null');
            DB::statement('alter table posts modify image_url varchar(500) null');
            DB::statement('alter table posts modify status varchar(30) not null default "published"');

            return;
        }

        DB::statement('alter table posts alter column title type varchar(180)');
        DB::statement('alter table posts alter column slug type varchar(200)');
        DB::statement('alter table posts alter column category type varchar(50)');
        DB::statement('alter table posts alter column image_url type varchar(500)');
        DB::statement('alter table posts alter column status type varchar(30)');
    }
};
