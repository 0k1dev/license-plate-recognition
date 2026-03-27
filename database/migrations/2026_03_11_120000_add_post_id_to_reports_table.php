<?php

declare(strict_types=1);

use App\Models\Post;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->foreignId('post_id')
                ->nullable()
                ->constrained('posts')
                ->nullOnDelete();
        });

        DB::table('reports')
            ->where('reportable_type', Post::class)
            ->whereNotNull('reportable_id')
            ->update([
                'post_id' => DB::raw('reportable_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_id');
        });
    }
};
