<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bảng lịch sử xem của user
        Schema::create('post_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')
                ->constrained('posts')
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->timestamp('viewed_at')->useCurrent();

            // Mỗi user chỉ lưu 1 record per post (upsert on duplicate)
            $table->unique(['user_id', 'post_id']);

            // Index tra cứu lịch sử nhanh
            $table->index(['user_id', 'viewed_at']);
            // Index đếm lượt xem theo post
            $table->index('post_id');
        });

        // Thêm cột views_count vào posts
        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedBigInteger('views_count')->default(0)->after('renew_count');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('views_count');
        });

        Schema::dropIfExists('post_views');
    }
};
