<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerPhoneRequestApproveRequest;
use App\Http\Requests\OwnerPhoneRequestRejectRequest;
use App\Http\Requests\StoreOwnerPhoneRequestRequest;
use App\Models\OwnerPhoneRequest;
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

        if ((int) $request->property_id !== $property) {
            abort(422, 'Mã bất động sản không khớp với yêu cầu.');
        }

        $phoneRequest = $this->service->createRequest(
            (int) $request->property_id,
            (int) $request->user()->id,
            $request->reason
        );

        return (new \App\Http\Resources\OwnerPhoneRequestResource($phoneRequest->load(['property', 'requester'])))
            ->response()
            ->setStatusCode(201);
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
