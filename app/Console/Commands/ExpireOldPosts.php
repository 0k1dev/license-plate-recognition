<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\PostService;
use Illuminate\Console\Command;

class ExpireOldPosts extends Command
{
    protected $signature = 'posts:expire';

    protected $description = 'Auto-expire posts với visible_until đã quá hạn';

    public function handle(PostService $postService): int
    {
        $expiredPosts = Post::where('status', 'VISIBLE')
            ->whereNotNull('visible_until')
            ->where('visible_until', '<', now())
            ->get();

        $count = 0;

        foreach ($expiredPosts as $post) {
            $postService->expire($post);
            $count++;
        }

        $this->info("Đã expire {$count} bài đăng hết hạn.");

        return self::SUCCESS;
    }
}
