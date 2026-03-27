<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPropertyApproveRequest;
use App\Http\Requests\AdminPropertyRejectRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\PropertyService;

use App\Http\Requests\Property\AdminListPropertyRequest;

class AdminPropertyController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * List all properties for admin (bypassing area scope).
     */
    public function index(AdminListPropertyRequest $request)
    {
        // Only admin can access
        $this->authorize('viewAny', Property::class);

        $validated = $request->validated();

        $query = Property::query()
            ->with(['areaLocation', 'project', 'category', 'creator', 'approver']);

        // Filters
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $validated['approval_status']);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $validated['category_id']);
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $validated['area_id']);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $validated['created_by']);
        }

        if ($request->filled('q')) {
            $q = $validated['q'];
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('street_name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('owner_name', 'like', "%{$q}%");
            });
        }

        // Sorting
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        // Pagination
        $limit = $validated['limit'] ?? 10;
        $properties = $query->paginate($limit);

        return PropertyResource::collection($properties);
    }

    /**
     * Approve property
     */
    public function approve(AdminPropertyApproveRequest $request, Property $property)
    {
        $this->authorize('approve', $property);

        $this->propertyService->approve(
            $property,
            $request->user(),
            $request->validated()['note'] ?? null
        );

        return response()->json([
            'message' => 'Property đã được phê duyệt thành công.',
            'data' => new PropertyResource($property->fresh())
        ]);
    }

    /**
     * Reject property
     */
    public function reject(AdminPropertyRejectRequest $request, Property $property)
    {
        $this->authorize('approve', $property);

        $this->propertyService->reject(
            $property,
            $request->user(),
            $request->validated()['reason']
        );

        return response()->json([
            'message' => 'Property đã bị từ chối.',
            'data' => new PropertyResource($property->fresh())
        ]);
    }
}
