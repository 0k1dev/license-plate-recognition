<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListProjectRequest;
use App\Models\Project;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;

class ProjectController extends Controller
{
    public function index(ListProjectRequest $request)
    {
        $areaId = $request->integer('area_id');

        $projects = Project::query()
            ->where('area_id', $areaId)
            ->with('area:id,name')
            ->orderBy('name')
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
