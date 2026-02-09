<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('vb_email_templates')) {
            Schema::create('vb_email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key')->unique()->comment('System key identifier');
                $table->string('subject');
                $table->longText('content')->nullable();
                $table->string('view')->nullable()->comment('Blade view path');
                $table->string('language', 10)->default('vi');
                $table->string('from')->nullable();
                $table->text('cc')->nullable();
                $table->text('bcc')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vb_email_templates');
    }
};
