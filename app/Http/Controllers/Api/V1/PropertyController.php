<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\PropertyService;
use App\Http\Requests\Property\ListPropertyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PropertyController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * List properties (with area scoping for FIELD_STAFF)
     */
    public function index(ListPropertyRequest $request)
    {
        $user = $request->user();

        $query = Property::query()
            ->with(['category', 'areaLocation', 'creator', 'images', 'orderedFiles', 'myApprovedPhoneRequest'])
            ->withinUserAreas($user);

        // Security Scope: FIELD_STAFF only sees APPROVED or their own
        if (!$user->isAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('approval_status', 'APPROVED')
                    ->orWhere('created_by', $user->id);
            });
        }

        // 1. Tìm kiếm từ khóa (q)
        if ($request->filled('q')) {
            $q = trim($request->input('q'));
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('street_name', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('district_id')) {
            $query->where('district_id', $request->input('district_id'));
        }
        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->input('ward_id'));
        }
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        if ($request->filled('area_min')) {
            $query->where('area', '>=', $request->input('area_min'));
        }
        if ($request->filled('area_max')) {
            $query->where('area', '<=', $request->input('area_max'));
        }

        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', $request->input('bedrooms'));
        }

        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', $request->input('bathrooms'));
        }
        if ($request->filled('floor')) {
            $query->where('floor', $request->input('floor'));
        }

        if ($request->filled('location_type')) {
            $query->where('location_type', $request->input('location_type'));
        }
        if ($request->filled('shape')) {
            $query->where('shape', $request->input('shape'));
        }
        if ($request->filled('legal_status')) {
            $query->where('legal_status', $request->input('legal_status'));
        }
        if ($request->filled('amenities') && is_array($request->input('amenities'))) {
            foreach ($request->input('amenities') as $amenity) {
                $query->whereJsonContains('amenities', $amenity);
            }
        }

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        $validated = $request->validated();
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        $limit = min((int)$request->input('limit', 10), 100);
        $properties = $query->paginate($limit)->withQueryString();

        return PropertyResource::collection($properties);
    }

    public function store(StorePropertyRequest $request)
    {
        $this->authorize('create', Property::class);

        $validated = $request->validated();
        $propertyImages = $this->resolveUploadedFiles($request, ['property_images', 'propertyImages']);
        $legalDocFiles = $this->resolveUploadedFiles($request, ['legal_doc_files', 'legal_doc_file', 'legalDocFiles']);

        try {
            $property = $this->propertyService->create(
                $request->user(),
                $validated,
                $propertyImages,
                $legalDocFiles
            );
        } catch (\Throwable $e) {
            Log::error('PROPERTY_CREATE_REQUEST_FAILED', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);
            throw $e;
        }

        return response()->json([
            'message' => 'Tạo BĐS thành công, đang chờ duyệt.',
            'property_id' => $property->id,
            'approval_status' => $property->approval_status,
        ], 201);
    }

    public function show(Request $request, Property $property)
    {
        $this->authorize('view', $property);

        $property->load(['areaLocation', 'project', 'category', 'creator', 'images', 'orderedFiles', 'myApprovedPhoneRequest'])
            ->loadCount('posts');

        return new PropertyResource($property);
    }

    public function update(UpdatePropertyRequest $request, Property $property)
    {
        $this->authorize('update', $property);

        $validated = $request->validated();
        $propertyImages = $this->resolveUploadedFiles($request, ['property_images', 'propertyImages']);
        $legalDocFiles = $this->resolveUploadedFiles($request, ['legal_doc_files', 'legal_doc_file', 'legalDocFiles']);
        $property = $this->propertyService->update(
            $property,
            $validated,
            $request->user(),
            $propertyImages,
            $legalDocFiles
        );

        return new PropertyResource($property->load(['areaLocation', 'project', 'category', 'orderedFiles']));
    }

    public function destroy(Request $request, Property $property)
    {
        $this->authorize('delete', $property);

        DB::transaction(function () use ($property): void {
            $property->delete();
            \App\Models\AuditLog::log('delete_property', Property::class, $property->id);
        });

        return response()->json(['message' => 'Đã xóa bất động sản thành công.']);
    }

    public function me(Request $request)
    {
        $user = $request->user();

        $query = Property::query()
            ->where('created_by', $user->id)
            ->with(['areaLocation', 'project', 'category', 'images', 'orderedFiles', 'creator'])
            ->withCount('posts');

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        $limit = min($request->input('limit', 10), 100);
        $properties = $query->latest()->paginate($limit);

        return PropertyResource::collection($properties);
    }

    public function map(Request $request)
    {
        $user = $request->user();

        $query = Property::query()
            ->select(['id', 'title', 'lat', 'lng', 'price', 'area_id', 'category_id'])
            ->where('approval_status', 'APPROVED')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->withinUserAreas($user);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('area_id')) {
            $query->where('area_id', $request->area_id);
        }

        $markers = $query->get();

        return response()->json([
            'data' => $markers->map(fn($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'lat' => (float) $p->lat,
                'lng' => (float) $p->lng,
                'price' => $p->price,
            ])
        ]);
    }

    private function resolveUploadedFiles(Request $request, array $keys): array
    {
        foreach ($keys as $key) {
            if (!$request->hasFile($key)) {
                continue;
            }
            $files = $request->file($key);
            return is_array($files) ? $files : [$files];
        }
        return [];
    }
}
