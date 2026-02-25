<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Http\Requests\StoreAreaRequest;
use App\Http\Requests\UpdateAreaRequest;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index(Request $request)
    {
        $areas = Area::query()
            ->when($request->level, fn($q, $v) => $q->where('level', $v))
            ->when($request->parent_id, fn($q, $v) => $q->where('parent_id', $v))
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        return response()->json(['data' => $areas]);
    }

    public function store(StoreAreaRequest $request)
    {
        $this->authorize('create', Area::class);
        $area = Area::create($request->validated());
        return response()->json(['data' => $area, 'message' => 'Tạo khu vực thành công.'], 201);
    }

    public function update(UpdateAreaRequest $request, Area $area)
    {
        $this->authorize('update', $area);
        $area->update($request->validated());
        return response()->json(['data' => $area, 'message' => 'Cập nhật khu vực thành công.']);
    }
}
