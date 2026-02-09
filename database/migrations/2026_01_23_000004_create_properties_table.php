<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');

            // Location
            $table->foreignId('area_id')->constrained();
            $table->foreignId('project_id')->nullable()->constrained();
            $table->foreignId('category_id')->nullable()->constrained();
            $table->string('address');

            // Owner info - SENSITIVE DATA
            $table->string('owner_name');
            $table->string('owner_phone'); // MASKED for FIELD_STAFF

            // Property details
            $table->decimal('price', 15, 2);
            $table->decimal('area', 10, 2)->comment('Diện tích m²');

            // Legal docs - SENSITIVE DATA
            $table->json('legal_docs')->nullable()->comment('MASKED - chỉ creator và admin');

            // Approval workflow
            $table->enum('approval_status', ['PENDING', 'APPROVED', 'REJECTED'])
                ->default('PENDING');
            $table->text('approval_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Creator
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['area_id', 'approval_status']);
            $table->index('created_by');
            $table->index('approval_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
