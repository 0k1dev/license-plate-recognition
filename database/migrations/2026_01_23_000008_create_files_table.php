<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->comment('Tên file trên server');
            $table->string('original_name')->comment('Tên file gốc');
            $table->string('path')->comment('Đường dẫn');
            $table->string('mime_type');
            $table->unsignedBigInteger('size')->comment('Bytes');

            $table->enum('purpose', [
                'PROPERTY_IMAGE',
                'AVATAR',
                'CCCD_FRONT',
                'CCCD_BACK',
                'LEGAL_DOC',
                'REPORT_EVIDENCE',
            ]);

            $table->enum('visibility', ['PUBLIC', 'PRIVATE'])->default('PUBLIC');

            // Polymorphic owner
            $table->morphs('owner'); // property, user, report

            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();

            $table->index(['owner_type', 'owner_id', 'purpose']);
            $table->index('purpose');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
