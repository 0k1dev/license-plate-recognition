<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tạo cột mới subdivision_id
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedBigInteger('subdivision_id')->nullable()->after('address');
            $table->index('subdivision_id');
        });

        // Migration dữ liệu: Copy ward_id -> subdivision_id
        DB::statement('UPDATE properties SET subdivision_id = ward_id WHERE ward_id IS NOT NULL');

        // Xóa district_id và ward_id
        Schema::table('properties', function (Blueprint $table) {
            // Drop foreign keys first
            $table->dropForeign(['district_id']);
            $table->dropForeign(['ward_id']);

            // Then drop columns
            $table->dropColumn(['district_id', 'ward_id']);
        });

        // Thêm foreign key cho subdivision_id
        Schema::table('properties', function (Blueprint $table) {
            $table->foreign('subdivision_id')->references('id')->on('areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // Drop foreign key subdivision_id
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['subdivision_id']);
        });

        // Recreate district_id and ward_id
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignId('district_id')->nullable()->after('address')->constrained('areas');
            $table->foreignId('ward_id')->nullable()->after('district_id')->constrained('areas');
        });

        // Copy data back
        DB::statement('UPDATE properties SET ward_id = subdivision_id WHERE subdivision_id IS NOT NULL');

        // Drop subdivision_id
        Schema::table('properties', function (Blueprint $table) {
            $table->dropIndex(['subdivision_id']);
            $table->dropColumn('subdivision_id');
        });
    }
};
