<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Post\ListPostRequest;
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

    /**
     * GET /api/v1/posts
     */
    public function index(ListPostRequest $request)
    {
        $validated = $request->validated();

        $query = Post::query()
            ->with([
                'property:id,title,description,price,area,address,street_name,category_id,subdivision_id,area_id,project_id,'
                    . 'bedrooms,bathrooms,floor,direction,location_type,shape,legal_status,legal_docs,amenities,'
                    . 'owner_phone,source_phone,source_code,created_by,approval_status,lat,lng,google_map_url,created_at',
                'property.images',
                'property.orderedFiles',
                'property.areaLocation:id,name',
                'property.category:id,name',
                'property.myApprovedPhoneRequest',
                'property.myLatestPhoneRequest',
                'property.creator:id,name,avatar_url',
                'creator:id,name,avatar_url',
            ])
            ->where('posts.status', 'VISIBLE')
            ->where(function ($q) {
                $q->whereNull('posts.visible_until')
                    ->orWhere('posts.visible_until', '>=', now());
            })
            ->whereHas('property', fn($q) => $q->where('approval_status', 'APPROVED'));

        $hasPropertyFilter = $request->hasAny([
            'q', 'street_name', 'category_id', 'district_id', 'ward_id', 'area_id', 'project_id',
            'price_min', 'price_max', 'area_min', 'area_max', 'bedrooms', 'bathrooms', 'floor',
            'direction', 'location_type', 'shape', 'legal_status', 'amenities',
        ]);

        if ($hasPropertyFilter) {
            $query->whereHas('property', function ($pq) use ($request) {
                if ($request->filled('q')) {
                    $kw = trim($request->input('q'));
                    $pq->where(function ($s) use ($kw) {
                        $s->where('title', 'like', "%{$kw}%")
                            ->orWhere('street_name', 'like', "%{$kw}%")
                            ->orWhere('address', 'like', "%{$kw}%")
                            ->orWhere('description', 'like', "%{$kw}%");
                    });
                }
                if ($request->filled('street_name')) {
                    $pq->where('street_name', 'like', '%' . trim((string) $request->input('street_name')) . '%');
                }
                if ($request->filled('category_id')) {
                    $pq->where('category_id', $request->integer('category_id'));
                }
                if ($request->filled('subdivision_id')) {
                    $pq->where('subdivision_id', $request->integer('subdivision_id'));
                }
                if ($request->filled('area_id')) {
                    $pq->where('area_id', $request->integer('area_id'));
                }
                if ($request->filled('project_id')) {
                    $pq->where('project_id', $request->integer('project_id'));
                }
                if ($request->filled('price_min')) {
                    $pq->where('price', '>=', $request->integer('price_min'));
                }
                if ($request->filled('price_max')) {
                    $pq->where('price', '<=', $request->integer('price_max'));
                }
                if ($request->filled('area_min')) {
                    $pq->where('area', '>=', $request->input('area_min'));
                }
                if ($request->filled('area_max')) {
                    $pq->where('area', '<=', $request->input('area_max'));
                }
                if ($request->filled('bedrooms')) {
                    $pq->where('bedrooms', $request->integer('bedrooms'));
                }
                if ($request->filled('bathrooms')) {
                    $pq->where('bathrooms', $request->integer('bathrooms'));
                }
                if ($request->filled('floor')) {
                    $pq->where('floor', $request->integer('floor'));
                }
                if ($request->filled('direction')) {
                    $pq->where('direction', $request->input('direction'));
                }
                if ($request->filled('location_type')) {
                    $pq->where('location_type', $request->input('location_type'));
                }
                if ($request->filled('shape')) {
                    $pq->where('shape', $request->input('shape'));
                }
                if ($request->filled('legal_status')) {
                    $pq->where('legal_status', $request->input('legal_status'));
                }
                if ($request->filled('amenities') && is_array($request->input('amenities'))) {
                    foreach ($request->input('amenities') as $amenity) {
                        $pq->whereJsonContains('amenities', $amenity);
                    }
                }
            });
        }

        $sort  = $validated['sort']  ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        if (in_array($sort, ['price', 'area'], true)) {
            $query->join('properties', 'properties.id', '=', 'posts.property_id')
                ->orderBy("properties.{$sort}", $order)
                ->select('posts.*');
        } else {
            $query->orderBy("posts.{$sort}", $order);
        }

        $limit = min((int) ($validated['limit'] ?? 10), 100);
        $posts = $query->paginate($limit)->withQueryString();

        return \App\Http\Resources\PostResource::collection($posts);
    }

    public function show(Request $request, Post $post)
    {
        $this->authorize('view', $post);

        $post->load([
            'property:id,title,description,price,area,address,street_name,category_id,subdivision_id,area_id,project_id,'
                . 'bedrooms,bathrooms,floor,direction,location_type,shape,legal_status,legal_docs,amenities,'
                . 'owner_phone,source_phone,source_code,created_by,approval_status,lat,lng,google_map_url,created_at',
            'property.images',
            'property.orderedFiles',
            'property.areaLocation:id,name',
            'property.category:id,name',
            'property.myApprovedPhoneRequest',
            'property.myLatestPhoneRequest',
            'property.creator:id,name,avatar_url',
            'creator:id,name,avatar_url',
        ]);

        return new \App\Http\Resources\PostResource($post);
    }

    public function store(StorePostRequest $request)
    {
        $this->authorize('create', Post::class);
        $post = $this->postService->create($request->user(), $request->validated());
        return (new \App\Http\Resources\PostResource($post))->response()->setStatusCode(201);
    }

    public function update(PostUpdateRequest $request, Post $post)
    {
        $this->authorize('update', $post);
        $data = $request->validated();

        DB::transaction(function () use ($post, $data): void {
            if (array_key_exists('status', $data)) {
                if ($data['status'] === 'VISIBLE') {
                    $this->postService->setVisible($post, $data['visible_until'] ?? $post->visible_until);
                } elseif ($data['status'] === 'HIDDEN') {
                    $this->postService->hide($post);
                } elseif ($data['status'] === 'EXPIRED') {
                    $this->postService->expire($post);
                } elseif ($data['status'] === 'PENDING') {
                    $post->update(['status' => 'PENDING']);
                }
            } elseif (array_key_exists('visible_until', $data)) {
                $this->postService->setVisible($post, $data['visible_until']);
            }
        });

        return new \App\Http\Resources\PostResource($post->fresh(['property', 'property.orderedFiles', 'property.myApprovedPhoneRequest', 'property.creator', 'creator']));
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

    public function me(Request $request)
    {
        $user = $request->user();
        $query = Post::query()
            ->where('created_by', $user->id)
            ->with([
                'property', 'property.images', 'property.orderedFiles', 'property.areaLocation', 
                'property.category', 'property.myApprovedPhoneRequest', 'property.creator', 'creator'
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $limit = min((int)$request->input('limit', 10), 100);
        $posts = $query->latest()->paginate($limit);

        return \App\Http\Resources\PostResource::collection($posts);
    }

    public function meShow(Request $request, Post $post)
    {
        $user = $request->user();
        abort_unless((int) $post->created_by === (int) $user->id, 404, 'Không tìm thấy tin đăng.');

        $post->load([
            'property:id,title,description,price,area,address,street_name,category_id,subdivision_id,area_id,project_id,'
                . 'bedrooms,bathrooms,floor,direction,location_type,shape,legal_status,legal_docs,amenities,'
                . 'owner_phone,source_phone,source_code,created_by,approval_status,lat,lng,google_map_url,created_at',
            'property.images',
            'property.orderedFiles',
            'property.areaLocation:id,name',
            'property.category:id,name',
            'property.myApprovedPhoneRequest',
            'property.myLatestPhoneRequest',
            'property.creator:id,name,avatar_url',
            'creator:id,name,avatar_url',
        ]);

        return new \App\Http\Resources\PostResource($post);
    }
}
