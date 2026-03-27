<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerPhoneRequestApproveRequest;
use App\Http\Requests\OwnerPhoneRequestRejectRequest;
use App\Http\Requests\StoreOwnerPhoneRequestRequest;
use App\Models\OwnerPhoneRequest;
use App\Models\Post;
use App\Services\OwnerPhoneRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerPhoneRequestController extends Controller
{
    public function __construct(
        protected OwnerPhoneRequestService $service
    ) {}

    /**
     * GET /me/owner-phone-requests – Danh sách yêu cầu SĐT của user đăng nhập.
     */
    public function myRequests(Request $request)
    {
        $requests = OwnerPhoneRequest::query()
            ->where('requester_id', $request->user()->id)
            ->with(['property', 'reviewer'])
            ->latest()
            ->paginate($request->input('limit', 10));

        return \App\Http\Resources\OwnerPhoneRequestResource::collection($requests);
    }

    public function store(StoreOwnerPhoneRequestRequest $request, int $property)
    {
        $this->authorize('create', OwnerPhoneRequest::class);

        $validated = $request->validated();

        $this->service->createRequest(
            (int) $property,
            (int) $request->user()->id,
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Tạo yêu cầu thành công.',
        ], 201);
    }

    /**
     * POST /posts/{post}/owner-phone-requests – Tạo yêu cầu xem SĐT từ post_id.
     */
    public function storeByPost(StoreOwnerPhoneRequestRequest $request, Post $post)
    {
        $this->authorize('create', OwnerPhoneRequest::class);

        $validated = $request->validated();

        $this->service->createRequest(
            (int) $post->property_id,
            (int) $request->user()->id,
            $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Tạo yêu cầu thành công.',
        ], 201);
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', OwnerPhoneRequest::class);

        $requests = OwnerPhoneRequest::query()
            ->with(['property', 'requester', 'reviewer'])
            ->latest()
            ->paginate($request->input('limit', 10));

        return \App\Http\Resources\OwnerPhoneRequestResource::collection($requests);
    }

    public function approve(OwnerPhoneRequestApproveRequest $request, OwnerPhoneRequest $ownerPhoneRequest)
    {
        $this->authorize('approve', $ownerPhoneRequest);

        DB::transaction(function () use ($request, $ownerPhoneRequest): void {
            $this->service->approve($ownerPhoneRequest, $request->user(), $request->validated()['note'] ?? null);
        });

        return response()->json(['message' => 'Yêu cầu đã được duyệt.']);
    }

    public function reject(OwnerPhoneRequestRejectRequest $request, OwnerPhoneRequest $ownerPhoneRequest)
    {
        $this->authorize('approve', $ownerPhoneRequest);

        DB::transaction(function () use ($request, $ownerPhoneRequest): void {
            $this->service->reject($ownerPhoneRequest, $request->user(), $request->validated()['reason']);
        });

        return response()->json(['message' => 'Yêu cầu đã bị từ chối.']);
    }
}
