<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: Alter ENUM column to include new values
        DB::statement("ALTER TABLE reports MODIFY COLUMN `status` ENUM('NEW','IN_REVIEW','RESOLVED','REJECTED','OPEN','IN_PROGRESS') NOT NULL DEFAULT 'OPEN'");

        // Step 2: Migrate existing data
        DB::table('reports')->where('status', 'NEW')->update(['status' => 'OPEN']);
        DB::table('reports')->where('status', 'IN_REVIEW')->update(['status' => 'IN_PROGRESS']);
        DB::table('reports')->where('status', 'REJECTED')->update(['status' => 'RESOLVED']);

        // Step 3: Remove old ENUM values
        DB::statement("ALTER TABLE reports MODIFY COLUMN `status` ENUM('OPEN','IN_PROGRESS','RESOLVED') NOT NULL DEFAULT 'OPEN'");

        // Step 4: Add NO_ACTION to action enum
        DB::statement("ALTER TABLE reports MODIFY COLUMN `action` ENUM('HIDE_POST','LOCK_USER','WARN','NO_ACTION') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE reports MODIFY COLUMN `status` ENUM('NEW','IN_REVIEW','RESOLVED','REJECTED') NOT NULL DEFAULT 'NEW'");
        DB::table('reports')->where('status', 'OPEN')->update(['status' => 'NEW']);
        DB::table('reports')->where('status', 'IN_PROGRESS')->update(['status' => 'IN_REVIEW']);

        DB::statement("ALTER TABLE reports MODIFY COLUMN `action` ENUM('HIDE_POST','LOCK_USER','WARN') NULL DEFAULT NULL");
    }
};
