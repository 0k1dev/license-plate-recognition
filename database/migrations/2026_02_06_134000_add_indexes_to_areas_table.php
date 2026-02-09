<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            // Index cho việc lọc cấp (Tỉnh/Huyện/Xã) -> Tăng tốc hiển thị danh sách ban đầu
            $table->index('level');

            // Index cho tìm kiếm theo tên -> Tăng tốc ô Search
            $table->index('name');

            // Compound index cho việc query con cái (VD: lấy tất cả huyện thuộc tỉnh X)
            $table->index(['parent_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropIndex(['level']);
            $table->dropIndex(['name']);
            $table->dropIndex(['parent_id', 'level']);
        });
    }
};
