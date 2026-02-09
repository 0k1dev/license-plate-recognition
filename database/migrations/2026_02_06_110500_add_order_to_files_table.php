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
            if (!Schema::hasColumn('files', 'order')) {
                $table->integer('order')->default(0)->after('is_primary')->comment('Thứ tự sắp xếp');
                $table->index(['owner_type', 'owner_id', 'order'], 'idx_files_owner_order');
            }
        });
    }

    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            $table->dropIndex('idx_files_owner_order');
            $table->dropColumn('order');
        });
    }
};
