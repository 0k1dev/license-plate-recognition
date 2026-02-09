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
            $table->foreignId('district_id')->nullable()->after('address')->constrained('areas');
            $table->foreignId('ward_id')->nullable()->after('district_id')->constrained('areas');

            $table->index(['district_id', 'ward_id']);
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropForeign(['district_id']);
            $table->dropForeign(['ward_id']);
            $table->dropColumn(['district_id', 'ward_id']);
        });
    }
};
