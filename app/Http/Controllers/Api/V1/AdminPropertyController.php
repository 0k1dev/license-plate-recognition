<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminPropertyApproveRequest;
use App\Http\Requests\AdminPropertyRejectRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\PropertyService;
use Illuminate\Http\Request;

class AdminPropertyController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * Admin list all properties (không bị area scoping)
     */
    public function index(Request $request)
    {
        // Only admin can access
        $this->authorize('viewAny', Property::class);

        $query = Property::query()
            ->with(['areaLocation', 'project', 'category', 'creator', 'approver']);

        // Filters
        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        if ($request->filled('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('owner_name', 'like', "%{$q}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'created_at');
        $order = $request->input('order', 'desc');
        $query->orderBy($sort, $order);

        // Pagination
        $limit = min($request->input('limit', 10), 100);
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
