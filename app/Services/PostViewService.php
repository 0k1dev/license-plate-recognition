<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\RecordPostView;
use App\Models\Post;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class PostViewService
{
    /**
     * Cooldown tính bằng giây — tránh đếm spam view.
     * Trong 30 phút, mỗi user chỉ được đếm 1 lần view cho 1 bài.
     */
    private const VIEW_COOLDOWN_SECONDS = 1800;

    /**
     * Ghi nhận lượt xem:
     * 1. Kiểm tra cooldown bằng Cache → bỏ qua nếu vừa xem
     * 2. Dispatch Job bất đồng bộ để ghi DB (không block response)
     *
     * @return bool true nếu view mới được ghi nhận, false nếu đang trong cooldown
     */
    public function record(Post $post, User $user): bool
    {
        $cacheKey = "post_view_cooldown:{$user->id}:{$post->id}";

        if (Cache::has($cacheKey)) {
            return false; // Đang trong cooldown, bỏ qua
        }

        // Đặt cooldown
        Cache::put($cacheKey, true, self::VIEW_COOLDOWN_SECONDS);

        // Dispatch job bất đồng bộ
        RecordPostView::dispatch($post->id, $user->id);

        return true;
    }

    /**
     * Lấy lịch sử tin đã xem của user, sắp xếp mới nhất lên trên.
     * Eager load đầy đủ để tránh N+1.
     */
    public function getHistory(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return Post::query()
            ->join('post_views', 'post_views.post_id', '=', 'posts.id')
            ->where('post_views.user_id', $user->id)
            ->whereNull('posts.deleted_at')
            ->with([
                'property:id,title,price,area,address,street_name,owner_phone,primary_image_url,primary_thumbnail_url,created_by',
                'property.images',
                'property.areaLocation:id,name',
                'property.category:id,name',
                'property.myApprovedPhoneRequest',
                'property.myLatestPhoneRequest',
                'creator:id,name,avatar_url',
            ])
            ->select('posts.*', 'post_views.viewed_at')
            ->orderBy('post_views.viewed_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Xóa toàn bộ lịch sử xem của user.
     */
    public function clearHistory(User $user): int
    {
        $deleted = \App\Models\PostView::where('user_id', $user->id)->delete();

        // Xóa cache cooldown liên quan (nếu muốn reset hoàn toàn)
        Cache::flush(); // Có thể thay bằng tagged cache nếu dùng Redis

        return $deleted;
    }
}
