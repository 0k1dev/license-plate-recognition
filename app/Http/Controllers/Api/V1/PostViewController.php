<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\PostViewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PostViewController extends Controller
{
    public function __construct(
        private readonly PostViewService $postViewService,
    ) {}

    /**
     * POST /api/v1/posts/{post}/view
     *
     * Ghi nhận lượt xem bài đăng.
     * - Chỉ đếm 1 lần trong 30 phút cho mỗi user/post (cooldown)
     * - Ghi DB bất đồng bộ qua Queue → không delay response
     */
    public function record(Request $request, Post $post): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Chỉ ghi view cho bài VISIBLE và còn hạn
        if ($post->status !== 'VISIBLE') {
            return response()->json([
                'recorded' => false,
                'reason'   => 'Post is not visible.',
            ]);
        }

        $recorded = $this->postViewService->record($post, $user);

        return response()->json([
            'recorded'    => $recorded,
            'views_count' => $post->views_count + ($recorded ? 1 : 0),
        ]);
    }

    /**
     * GET /api/v1/me/post-history
     *
     * Lấy lịch sử tin đã xem của user hiện tại.
     * Hỗ trợ pagination: ?per_page=10 (max 50)
     */
    public function history(Request $request): AnonymousResourceCollection
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $perPage = min((int) $request->input('per_page', 10), 50);

        $posts = $this->postViewService->getHistory($user, $perPage);

        return \App\Http\Resources\PostResource::collection($posts);
    }

    /**
     * DELETE /api/v1/me/post-history
     *
     * Xóa toàn bộ lịch sử xem.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $deleted = $this->postViewService->clearHistory($user);

        return response()->json([
            'message' => 'Đã xóa lịch sử xem.',
            'deleted' => $deleted,
        ]);
    }
}
