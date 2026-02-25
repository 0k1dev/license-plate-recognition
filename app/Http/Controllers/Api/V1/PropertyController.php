<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Models\Property;
use App\Services\PropertyService;
use App\Http\Requests\Property\ListPropertyRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService
    ) {}

    /**
     * List properties (with area scoping for FIELD_STAFF)
     * 
     * Lấy danh sách BĐS với bộ lọc đầy đủ.
     * 
     * @query string|null $q Từ khóa tìm kiếm (tiêu đề, địa chỉ, mô tả)
     * @query int|null $category_id Lọc theo ID danh mục
     * @query int|null $district_id Lọc theo ID quận/huyện
     * @query int|null $ward_id Lọc theo ID phường/xã
     * @query int|null $project_id Lọc theo ID dự án
     * @query int|null $price_min Giá tối thiểu (VND)
     * @query int|null $price_max Giá tối đa (VND)
     * @query float|null $area_min Diện tích tối thiểu (m2)
     * @query float|null $area_max Diện tích tối đa (m2)
     * @query int|null $bedrooms Số phòng ngủ
     * @query int|null $bathrooms Số phòng tắm
     * @query int|null $floor Số tầng
     * @query string|null $direction Hướng nhà (Đông, Tây, Nam, Bắc...)
     * @query string|null $location_type Loại vị trí (Mặt tiền, Hẻm ngõ...)
     * @query string|null $shape Hình dạng đất (Nở hậu, Vuông vức...)
     * @query string|null $legal_status Tình trạng pháp lý (Sổ đỏ, Sổ hồng...)
     * @query array|null $amenities Danh sách tiện ích (Bể bơi, Garage...) - Truyền dạng mảng amenities[]=...
     * @query string $sort Trường sắp xếp (mặc định: created_at). Các giá trị: price, area, created_at
     * @query string $order Thứ tự sắp xếp (asc/desc, mặc định: desc)
     * @query int $limit Số lượng bản ghi mỗi trang (mặc định: 10, max: 100)
     * @query int $page Trang hiện tại (mặc định: 1)
     */
    public function index(ListPropertyRequest $request)
    {
        $user = $request->user();

        $query = Property::query()
            ->with(['category', 'areaLocation', 'creator', 'images', 'myApprovedPhoneRequest'])
            ->withinUserAreas($user);

        // Security Scope: FIELD_STAFF only sees APPROVED or their own
        if (! $user->isSuperAdmin() && ! $user->isOfficeAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->where('approval_status', 'APPROVED')
                    ->orWhere('created_by', $user->id);
            });
        }

        // --- FILTERING LOGIC ---



        // 1. Tìm kiếm từ khóa (q)
        if ($request->filled('q')) {
            $q = trim($request->input('q')); // Trim whitespace
            $query->where(function ($query) use ($q) {
                $query->where('title', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        // 2. Lọc Theo danh mục
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        // 3. Quận Huyện & Phường Xã (Dùng cột district_id và ward_id mới)
        if ($request->filled('district_id')) {
            $query->where('district_id', $request->input('district_id'));
        }
        if ($request->filled('ward_id')) {
            $query->where('ward_id', $request->input('ward_id'));
        }
        // Fallback cho area_id cũ
        if ($request->filled('area_id')) {
            $query->where('area_id', $request->input('area_id'));
        }

        // 4. Dự Án
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        // 5. Mức giá
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        // 6. Diện tích
        if ($request->filled('area_min')) {
            $query->where('area', '>=', $request->input('area_min'));
        }
        if ($request->filled('area_max')) {
            $query->where('area', '<=', $request->input('area_max'));
        }

        // 7. Số phòng ngủ
        if ($request->filled('bedrooms')) {
            $query->where('bedrooms', $request->input('bedrooms'));
        }

        // 8. Hướng
        if ($request->filled('direction')) {
            $query->where('direction', $request->input('direction'));
        }

        // 9. Bổ sung: Số phòng tắm & Tầng
        if ($request->filled('bathrooms')) {
            $query->where('bathrooms', $request->input('bathrooms'));
        }
        if ($request->filled('floor')) {
            $query->where('floor', $request->input('floor'));
        }

        // 10. Các trường mới bổ sung theo Design
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

        // Sorting (validated by ListPropertyRequest: only created_at, price, area allowed)
        $validated = $request->validated();
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';
        $query->orderBy($sort, $order);

        // Pagination - Appends query parameters to pagination links!
        $limit = min((int)$request->input('limit', 10), 100);
        $properties = $query->paginate($limit)->withQueryString();

        return PropertyResource::collection($properties);
    }

    /**
     * Create new property (auto set to PENDING)
     */
    public function store(StorePropertyRequest $request)
    {
        $this->authorize('create', Property::class);

        $property = $this->propertyService->create(
            $request->user(),
            $request->validated()
        );

        return (new PropertyResource($property->load(['areaLocation', 'project', 'category'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show property detail (with data masking)
     */
    public function show(Request $request, Property $property)
    {
        $this->authorize('view', $property);

        $property->load(['areaLocation', 'project', 'category', 'creator', 'images', 'myApprovedPhoneRequest']);

        return new PropertyResource($property);
    }

    /**
     * Update property
     */
    public function update(StorePropertyRequest $request, Property $property)
    {
        $this->authorize('update', $property);

        $property = $this->propertyService->update($property, $request->validated());

        return new PropertyResource($property->load(['areaLocation', 'project', 'category']));
    }

    /**
     * Delete property (soft delete)
     */
    public function destroy(Request $request, Property $property)
    {
        $this->authorize('delete', $property);

        DB::transaction(function () use ($property): void {
            $property->delete();
            \App\Models\AuditLog::log('delete_property', Property::class, $property->id);
        });

        return response()->json([
            'message' => 'Đã xóa bất động sản thành công.'
        ]);
    }

    /**
     * List my properties
     */
    public function me(Request $request)
    {
        $user = $request->user();

        $query = Property::query()
            ->where('created_by', $user->id)
            ->with(['areaLocation', 'project', 'category', 'images', 'creator']);

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->approval_status);
        }

        $limit = min($request->input('limit', 10), 100);
        $properties = $query->latest()->paginate($limit);

        return PropertyResource::collection($properties);
    }

    /**
     * Get map markers
     */
    public function map(Request $request)
    {
        $user = $request->user();

        $query = Property::query()
            ->select(['id', 'title', 'lat', 'lng', 'price', 'area_id', 'category_id'])
            ->where('approval_status', 'APPROVED')
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->withinUserAreas($user);

        // Filters
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
}
