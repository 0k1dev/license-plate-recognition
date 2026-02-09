<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            // Drop khóa ngoại cũ (RESTRICT)
            $table->dropForeign(['area_id']);

            // Tạo khóa ngoại mới (CASCADE)
            // Xóa Area -> Xóa Property
            $table->foreign('area_id')
                ->references('id')
                ->on('areas')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['area_id']);

            // Revert về RESTRICT
            $table->foreign('area_id')->references('id')->on('areas');
        });
    }
};
