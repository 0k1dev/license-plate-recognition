<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = Project::query()
            ->when($request->area_id, fn($q, $v) => $q->where('area_id', $v))
            ->with('area:id,name')
            ->get();

        return response()->json(['data' => $projects]);
    }

    public function store(StoreProjectRequest $request)
    {
        $this->authorize('create', Project::class);
        $project = Project::create($request->validated());
        return response()->json(['data' => $project->load('area:id,name'), 'message' => 'Tạo dự án thành công.'], 201);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);
        $project->update($request->validated());
        return response()->json(['data' => $project->load('area:id,name'), 'message' => 'Cập nhật dự án thành công.']);
    }
}
