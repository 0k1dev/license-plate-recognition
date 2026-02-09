<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Kích thước đất
            $table->double('width')->nullable()->after('area')->comment('Chiều ngang (m)');
            $table->double('length')->nullable()->after('width')->comment('Chiều dài (m)');

            // Đường vào và vị trí
            $table->string('road_width')->nullable()->after('address')->comment('Độ rộng đường vào (m)');
            $table->string('shape')->nullable()->after('road_width')->comment('Hình dạng: Vuông vức, Tóp hậu, Nở hậu...');
            $table->string('location_type')->nullable()->after('shape')->comment('Vị trí: Mặt tiền, Ngõ hẻm, Trong ngõ...');

            // NOTE: district_id và ward_id đã tồn tại từ migration 2026_02_03_000001
            // Không cần thêm district và ward dạng string nữa

            // Media
            $table->string('video_url')->nullable()->after('legal_status')->comment('Link Youtube/TikTok');

            // Tiện ích xung quanh (lưu dạng JSON array)
            $table->json('amenities')->nullable()->after('video_url')->comment('Tiện ích: Gần chợ, trường học, bệnh viện...');

            // Năm xây dựng
            $table->year('year_built')->nullable()->after('floor')->comment('Năm xây dựng');

            // Indexes cho các cột thường dùng filter
            $table->index('location_type');
            $table->index('shape');
            $table->index('year_built');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'width',
                'length',
                'road_width',
                'shape',
                'location_type',
                'video_url',
                'amenities',
                'year_built'
            ]);
        });
    }
};
