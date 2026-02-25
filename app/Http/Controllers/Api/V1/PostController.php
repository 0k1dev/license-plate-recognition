<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostUpdateRequest;
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    public function __construct(
        protected \App\Services\PostService $postService
    ) {}

    public function index(Request $request)
    {
        $query = Post::query()->with(['property', 'creator']);

        $query->where('status', 'VISIBLE')
            ->where(function ($q) {
                $q->whereNull('visible_until')->orWhere('visible_until', '>=', now());
            });

        $posts = $query->latest()->paginate(10);

        return \App\Http\Resources\PostResource::collection($posts);
    }

    public function store(StorePostRequest $request)
    {
        $this->authorize('create', Post::class);

        $post = $this->postService->create($request->user(), $request->validated());

        return (new \App\Http\Resources\PostResource($post))
            ->response()
            ->setStatusCode(201);
    }

    public function update(PostUpdateRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $data = $request->validated();

        DB::transaction(function () use ($post, $data): void {
            if (array_key_exists('status', $data)) {
                if ($data['status'] === 'VISIBLE') {
                    $until = $data['visible_until'] ?? $post->visible_until;
                    $this->postService->setVisible($post, $until);
                    return;
                }

                if ($data['status'] === 'HIDDEN') {
                    $this->postService->hide($post);
                    return;
                }

                if ($data['status'] === 'EXPIRED') {
                    $this->postService->expire($post);
                    return;
                }

                if ($data['status'] === 'PENDING') {
                    $post->update(['status' => 'PENDING']);
                    return;
                }
            }

            if (array_key_exists('visible_until', $data)) {
                $this->postService->setVisible($post, $data['visible_until']);
            }
        });

        return new \App\Http\Resources\PostResource($post->fresh(['property', 'creator']));
    }

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $this->postService->delete($post, 'Người dùng tự xóa');
        return response()->json(['message' => 'Đã xóa tin đăng thành công.']);
    }

    public function hide(Request $request, Post $post)
    {
        $this->authorize('update', $post);
        $this->postService->hide($post, $request->input('reason'));
        return response()->json(['message' => 'Đã ẩn tin đăng thành công.']);
    }

    public function renew(Request $request, Post $post)
    {
        $this->authorize('update', $post);
        $this->postService->renew($post);
        return response()->json(['message' => 'Đã gia hạn tin đăng thành công.']);
    }

    /**
     * List my posts
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $query = Post::query()
            ->where('created_by', $user->id)
            ->with(['property', 'creator', 'property.areaLocation', 'property.category']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $limit = min((int)$request->input('limit', 10), 100);
        $posts = $query->latest()->paginate($limit);

        return \App\Http\Resources\PostResource::collection($posts);
    }
}
