<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation
            $table->morphs('reportable'); // post, property, user

            $table->foreignId('reporter_id')->constrained('users');
            $table->string('type', 50)->comment('SPAM, FAKE_INFO, OFFENSIVE, etc.');
            $table->text('content')->comment('Nội dung báo cáo');

            // Workflow
            $table->enum('status', ['NEW', 'IN_REVIEW', 'RESOLVED', 'REJECTED'])
                ->default('NEW');
            $table->enum('action', ['HIDE_POST', 'LOCK_USER', 'WARN'])->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('reporter_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
