<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE `files` MODIFY `purpose` VARCHAR(64) NOT NULL");
            return;
        }

        Schema::table('files', function (Blueprint $table): void {
            $table->string('purpose', 64)->change();
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement(
                "ALTER TABLE `files` MODIFY `purpose` ENUM(" .
                "'PROPERTY_IMAGE','AVATAR','SO_DO','HOP_DONG_MB','VI_BANG','CHO_SO','KHAC'" .
                ") NOT NULL"
            );
            return;
        }

        Schema::table('files', function (Blueprint $table): void {
            $table->string('purpose', 32)->change();
        });
    }
};
