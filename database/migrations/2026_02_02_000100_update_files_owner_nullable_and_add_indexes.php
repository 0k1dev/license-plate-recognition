<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function hasIndex(string $table, string $index): bool
    {
        $result = DB::selectOne(
            'SELECT COUNT(1) AS c
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = ?
               AND index_name = ?',
            [$table, $index]
        );

        return (int) ($result->c ?? 0) > 0;
    }

    public function up(): void
    {
        // Make files.owner_type/owner_id nullable without requiring doctrine/dbal.
        DB::statement('ALTER TABLE files MODIFY owner_type VARCHAR(255) NULL');
        DB::statement('ALTER TABLE files MODIFY owner_id BIGINT UNSIGNED NULL');

        Schema::table('properties', function (Blueprint $table) {
            if (! $this->hasIndex('properties', 'properties_project_id_index')) {
                $table->index('project_id');
            }
            if (! $this->hasIndex('properties', 'properties_category_id_index')) {
                $table->index('category_id');
            }
            if (! $this->hasIndex('properties', 'properties_price_index')) {
                $table->index('price');
            }
            if (! $this->hasIndex('properties', 'properties_area_index')) {
                $table->index('area');
            }
        });

        Schema::table('owner_phone_requests', function (Blueprint $table) {
            if (! $this->hasIndex('owner_phone_requests', 'owner_phone_requests_requester_id_index')) {
                $table->index('requester_id');
            }
            if (! $this->hasIndex('owner_phone_requests', 'owner_phone_requests_property_id_index')) {
                $table->index('property_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('owner_phone_requests', function (Blueprint $table) {
            if ($this->hasIndex('owner_phone_requests', 'owner_phone_requests_requester_id_index')) {
                $table->dropIndex(['requester_id']);
            }
            if ($this->hasIndex('owner_phone_requests', 'owner_phone_requests_property_id_index')) {
                $table->dropIndex(['property_id']);
            }
        });

        Schema::table('properties', function (Blueprint $table) {
            if ($this->hasIndex('properties', 'properties_project_id_index')) {
                $table->dropIndex(['project_id']);
            }
            if ($this->hasIndex('properties', 'properties_category_id_index')) {
                $table->dropIndex(['category_id']);
            }
            if ($this->hasIndex('properties', 'properties_price_index')) {
                $table->dropIndex(['price']);
            }
            if ($this->hasIndex('properties', 'properties_area_index')) {
                $table->dropIndex(['area']);
            }
        });

        // Revert to NOT NULL
        DB::statement('ALTER TABLE files MODIFY owner_type VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE files MODIFY owner_id BIGINT UNSIGNED NOT NULL');
    }
};
