<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['PENDING', 'VISIBLE', 'HIDDEN', 'EXPIRED'])
                ->default('PENDING');
            $table->timestamp('visible_until')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'visible_until']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
