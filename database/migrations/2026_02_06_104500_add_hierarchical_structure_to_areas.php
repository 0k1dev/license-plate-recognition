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
            // Mã code từ API (code từ provinces.open-api.vn)
            $table->integer('api_code')->nullable()->after('code')->comment('Code từ API provinces.open-api.vn');

            // Loại đơn vị hành chính (tỉnh, quận, phường, v.v.)
            $table->string('division_type')->nullable()->after('api_code')->comment('tỉnh, thành phố trung ương, quận, huyện, phường, xã, v.v.');

            // Codename để SEO-friendly
            $table->string('codename')->nullable()->after('division_type')->comment('SEO-friendly name');

            // Phone code (mã vùng điện thoại)
            $table->integer('phone_code')->nullable()->after('codename')->comment('Mã vùng điện thoại');

            // Phân cấp: province, district, ward
            $table->enum('level', ['province', 'district', 'ward'])->default('province')->after('phone_code')->comment('Cấp hành chính');

            // Parent ID để tạo cây phân cấp
            $table->unsignedBigInteger('parent_id')->nullable()->after('level')->comment('ID của cấp cha');

            // Path đầy đủ
            $table->string('path', 500)->nullable()->after('parent_id')->comment('Đường dẫn đầy đủ');

            // Thứ tự sắp xếp
            $table->integer('order')->default(0)->after('path')->comment('Thứ tự hiển thị');

            // Indexes để tăng performance
            $table->index('api_code', 'idx_areas_api_code');
            $table->index('level', 'idx_areas_level');
            $table->index('parent_id', 'idx_areas_parent_id');
            $table->index('division_type', 'idx_areas_division_type');
            $table->index(['level', 'parent_id'], 'idx_areas_level_parent');

            // Foreign key
            $table->foreign('parent_id', 'fk_areas_parent')
                ->references('id')
                ->on('areas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table) {
            $table->dropForeign('fk_areas_parent');
            $table->dropIndex('idx_areas_api_code');
            $table->dropIndex('idx_areas_level');
            $table->dropIndex('idx_areas_parent_id');
            $table->dropIndex('idx_areas_division_type');
            $table->dropIndex('idx_areas_level_parent');

            $table->dropColumn([
                'api_code',
                'division_type',
                'codename',
                'phone_code',
                'level',
                'parent_id',
                'path',
                'order',
            ]);
        });
    }
};
