<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('owner_phone_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained();
            $table->foreignId('requester_id')->constrained('users');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])
                ->default('PENDING');
            $table->text('reason')->nullable()->comment('Lý do xin xem');
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Chống duplicate pending requests
            $table->unique(['property_id', 'requester_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_phone_requests');
    }
};
