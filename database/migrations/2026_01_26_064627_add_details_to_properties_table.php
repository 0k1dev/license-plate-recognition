<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->unsignedTinyInteger('bedrooms')->nullable()->after('area');
            $table->unsignedTinyInteger('bathrooms')->nullable()->after('bedrooms');
            $table->string('direction')->nullable()->after('bathrooms'); // Dong, Tay, Nam, Bac...
            $table->string('floor')->nullable()->after('direction'); // Tang 5, 3 tang...

            // Location
            $table->decimal('lat', 10, 8)->nullable()->after('address');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');

            // Legal
            $table->string('legal_status')->nullable()->after('legal_docs')
                ->comment('SO_DO, HOP_DONG_MB, VI_BANG, CHO_SO, KHAC');

            // Indexes for frequently filtered columns
            $table->index(['lat', 'lng']);
            $table->index('legal_status');
            $table->index('bedrooms');
            $table->index('direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn([
                'bedrooms',
                'bathrooms',
                'direction',
                'floor',
                'lat',
                'lng',
                'legal_status'
            ]);
        });
    }
};
