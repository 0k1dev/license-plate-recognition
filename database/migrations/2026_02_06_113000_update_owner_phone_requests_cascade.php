<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('owner_phone_requests', function (Blueprint $table) {
            // Drop khóa ngoại cũ
            $table->dropForeign(['property_id']);

            // Re-add với cascade
            // Xóa Property -> Xóa Request
            $table->foreign('property_id')
                ->references('id')
                ->on('properties')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('owner_phone_requests', function (Blueprint $table) {
            $table->dropForeign(['property_id']);

            // Revert (mặc định RESTRICT)
            $table->foreign('property_id')->references('id')->on('properties');
        });
    }
};
