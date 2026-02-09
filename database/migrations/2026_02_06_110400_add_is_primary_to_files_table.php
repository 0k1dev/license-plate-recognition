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
            if (!Schema::hasColumn('files', 'is_primary')) {
                $table->boolean('is_primary')->default(false)->after('purpose')->comment('Đánh dấu ảnh chính của property');
                $table->index(['owner_type', 'owner_id', 'is_primary'], 'idx_files_owner_primary');
            }
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('idx_files_owner_primary');
            $table->dropColumn('is_primary');
        });
    }
};
