<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PostService
{
    public function create(User $user, array $data): Post
    {
        // Kiểm tra BĐS phải được duyệt (APPROVED) mới tạo bài đăng
        $property = Property::findOrFail($data['property_id']);
        if ($property->approval_status !== 'APPROVED') {
            throw ValidationException::withMessages([
                'property_id' => ['Chỉ có thể tạo bài đăng cho BĐS đã được duyệt.'],
            ]);
        }

        // Check if there is already an active post for this property
        $existingPost = Post::where('property_id', $data['property_id'])
            ->whereIn('status', ['VISIBLE', 'PENDING'])
            ->first();

        if ($existingPost) {
            throw ValidationException::withMessages([
                'property_id' => ['Bất động sản này đang có bài đăng hoạt động.'],
            ]);
        }

        $isAdmin = $user->isSuperAdmin() || $user->isOfficeAdmin();
        $status = $isAdmin ? 'VISIBLE' : 'PENDING';

        $post = Post::create([
            'property_id' => $data['property_id'],
            'status' => $status,
            'visible_until' => $status === 'VISIBLE'
                ? ($data['visible_until'] ?? now()->addDays(30))
                : null,
            // Creator of the post must follow the creator of the property.
            'created_by' => (int) $property->created_by,
        ]);

        AuditLog::log('create_post', Post::class, $post->id, ['initial_status' => $status]);

        return $post;
    }

    public function approve(Post $post): void
    {
        $post->update([
            'status' => 'VISIBLE',
            'visible_until' => now()->addDays(30),
        ]);

        AuditLog::log('approve_post', Post::class, $post->id, ['approved_at' => now()]);
    }

    public function renew(Post $post, int $days = 30): void
    {
        $maxRenew = (int) config('bds.max_post_renew', 3);
        if ($post->renew_count >= $maxRenew) {
            throw ValidationException::withMessages([
                'renew' => ["Đã vượt giới hạn gia hạn ({$maxRenew} lần)."],
            ]);
        }

        $newExpiry = $post->visible_until ? $post->visible_until->addDays($days) : now()->addDays($days);

        $post->update([
            'status' => 'VISIBLE',
            'visible_until' => $newExpiry,
            'renew_count' => $post->renew_count + 1,
        ]);

        AuditLog::log('renew_post', Post::class, $post->id, [
            'new_expiry' => $newExpiry,
            'renew_count' => $post->renew_count,
        ]);
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
