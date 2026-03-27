<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Ghi lại lượt xem bài đăng bất đồng bộ qua Queue.
 * Tránh làm chậm API response khi DB write.
 */
class RecordPostView implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        public readonly int $postId,
        public readonly int $userId,
    ) {}

    public function handle(): void
    {
        // Upsert: nếu đã tồn tại thì chỉ update viewed_at, không tạo duplicate
        \App\Models\PostView::upsert(
            [
                [
                    'post_id'   => $this->postId,
                    'user_id'   => $this->userId,
                    'viewed_at' => now(),
                ],
            ],
            uniqueBy: ['post_id', 'user_id'],
            update: ['viewed_at'],
        );

        // Tăng views_count trực tiếp (atomic increment, không gây race condition)
        Post::where('id', $this->postId)->increment('views_count');
    }

    /**
     * Unique key: chỉ dispatch 1 job per user+post trong queue
     * (tránh flood job khi user scroll nhanh)
     */
    public function uniqueId(): string
    {
        return "post_view_{$this->postId}_{$this->userId}";
    }
}
