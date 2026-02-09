<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Post;
use App\Models\User;

class PostService
{
    public function create(User $user, array $data): Post
    {
        // Check if there is already an active post for this property
        $existingPost = Post::where('property_id', $data['property_id'])
            ->whereIn('status', ['VISIBLE', 'PENDING'])
            ->first();

        if ($existingPost) {
            throw new \Exception('Bất động sản này đang có bài đăng hoạt động.');
        }

        $isAdmin = $user->isSuperAdmin() || $user->isOfficeAdmin();
        $status = $isAdmin ? 'VISIBLE' : 'PENDING';

        $post = Post::create([
            'property_id' => $data['property_id'],
            'status' => $status,
            'visible_until' => $status === 'VISIBLE'
                ? ($data['visible_until'] ?? now()->addDays(30))
                : null,
            'created_by' => $user->id,
        ]);

        AuditLog::log('create_post', Post::class, $post->id, ['initial_status' => $status]);

        return $post;
    }

    public function renew(Post $post, int $days = 30): void
    {
        $newExpiry = $post->visible_until ? $post->visible_until->addDays($days) : now()->addDays($days);

        $post->update([
            'status' => 'VISIBLE',
            'visible_until' => $newExpiry,
        ]);

        AuditLog::log('renew_post', Post::class, $post->id, ['new_expiry' => $newExpiry]);
    }

    public function delete(Post $post, ?string $reason = null): void
    {
        $post->delete(); // Soft delete

        AuditLog::log('delete_post', Post::class, $post->id, ['reason' => $reason]);
    }

    public function setVisible(Post $post, ?\DateTimeInterface $until = null): void
    {
        $post->update([
            'status' => 'VISIBLE',
            'visible_until' => $until,
        ]);

        AuditLog::log('update_post_status', Post::class, $post->id, [
            'status' => 'VISIBLE',
            'until' => $until,
        ]);
    }

    public function hide(Post $post, ?string $reason = null): void
    {
        $post->update([
            'status' => 'HIDDEN',
        ]);

        AuditLog::log('hide_post', Post::class, $post->id, [
            'reason' => $reason,
        ]);
    }

    public function expire(Post $post): void
    {
        $post->update([
            'status' => 'EXPIRED',
        ]);
    }
}
