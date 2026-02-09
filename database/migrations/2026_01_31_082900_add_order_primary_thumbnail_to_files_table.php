<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('visibility')
                ->comment('Thứ tự sắp xếp, 0 = đầu tiên');
            $table->boolean('is_primary')->default(false)->after('order')
                ->comment('Ảnh đại diện chính');
            $table->string('thumbnail_path')->nullable()->after('path')
                ->comment('Đường dẫn thumbnail');

            // Index for ordering
            $table->index(['owner_type', 'owner_id', 'order']);
            $table->index(['owner_type', 'owner_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex(['owner_type', 'owner_id', 'order']);
            $table->dropIndex(['owner_type', 'owner_id', 'is_primary']);
            $table->dropColumn(['order', 'is_primary', 'thumbnail_path']);
        });
    }
};
