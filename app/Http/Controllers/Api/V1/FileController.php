<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileReorderRequest;
use App\Http\Requests\FileStoreMultipleRequest;
use App\Http\Requests\FileUploadRequest;
use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    public function __construct(
        protected FileService $service
    ) {}

    /**
     * Upload single file
     *
     * @bodyParam file file required The file to upload. Example: image.jpg
     * @bodyParam purpose string required Purpose of file. Example: PROPERTY_IMAGE
     * @bodyParam owner_type string Owner model class. Example: App\Models\Property
     * @bodyParam owner_id int Owner ID. Example: 1
     * @bodyParam visibility string PUBLIC or PRIVATE. Example: PUBLIC
     * @bodyParam order int Display order. Example: 0
     * @bodyParam is_primary bool Set as primary image. Example: true
     */
    public function store(FileUploadRequest $request)
    {
        $file = $this->service->upload(
            $request->file('file'),
            $request->user(),
            $request->purpose,
            $request->owner_type ?? 'App\Models\User',
            (int) ($request->owner_id ?? $request->user()->id),
            $request->visibility ?? 'PUBLIC',
            (int) ($request->order ?? 0),
            (bool) ($request->is_primary ?? false)
        );

        return response()->json([
            'message' => 'Upload thành công',
            'data' => new \App\Http\Resources\FileResource($file)
        ], 201);
    }

    /**
     * Upload multiple files at once
     *
     * @bodyParam files file[] required Array of files to upload. Example: [image1.jpg, image2.jpg]
     * @bodyParam purpose string required Purpose of files. Example: PROPERTY_IMAGE
     * @bodyParam owner_type string Owner model class. Example: App\Models\Property
     * @bodyParam owner_id int Owner ID. Example: 1
     * @bodyParam visibility string PUBLIC or PRIVATE. Example: PUBLIC
     * @bodyParam primary_index int Index of file to set as primary (0-based). Example: 0
     */
    public function storeMultiple(FileStoreMultipleRequest $request)
    {
        $files = $this->service->uploadMultiple(
            $request->file('files'),
            $request->user(),
            $request->purpose,
            $request->owner_type ?? 'App\Models\User',
            (int) ($request->owner_id ?? $request->user()->id),
            $request->visibility ?? 'PUBLIC',
            $request->primary_index !== null ? (int) $request->primary_index : null
        );

        return response()->json([
            'message' => 'Upload ' . count($files) . ' files thành công',
            'data' => \App\Http\Resources\FileResource::collection($files)
        ], 201);
    }

    /**
     * Download a file
     */
    public function download(Request $request, File $file)
    {
        $this->authorize('view', $file);

        // Check permission if file is private
        if ($file->visibility === 'PRIVATE') {
            $user = $request->user();
            $isUploader = $user && $file->uploaded_by === $user->id;
            $isAdmin = $user && ($user->isSuperAdmin() || $user->isOfficeAdmin());

            // Check if user is property owner
            $isOwner = false;
            if ($file->owner_type === 'App\Models\Property' && $user) {
                $property = \App\Models\Property::find($file->owner_id);
                $isOwner = $property && $property->created_by === $user->id;
            }

            if (! $isUploader && ! $isAdmin && ! $isOwner) {
                abort(403, 'Bạn không có quyền tải file này.');
            }
        }

        $disk = $file->visibility === 'PRIVATE' ? 'local' : 'public';

        if (! Storage::disk($disk)->exists($file->path)) {
            abort(404, 'File không tồn tại.');
        }

        return Storage::disk($disk)->download($file->path, $file->original_name);
    }

    /**
     * Update file order and primary status
     */
    public function reorder(FileReorderRequest $request)
    {
        $this->authorize('update', File::class);

        foreach ($request->validated()['files'] as $fileData) {
            File::where('id', $fileData['id'])->update([
                'order' => $fileData['order'],
                'is_primary' => $fileData['is_primary'] ?? false,
            ]);
        }

        return response()->json(['message' => 'Cập nhật thứ tự thành công']);
    }

    /**
     * Set a file as primary
     */
    public function setPrimary(Request $request, File $file)
    {
        $this->authorize('update', $file);

        // Validate ownership
        $user = $request->user();
        $isUploader = $file->uploaded_by === $user->id;
        $isAdmin = $user->isSuperAdmin() || $user->isOfficeAdmin();

        if (! $isUploader && ! $isAdmin) {
            abort(403, 'Bạn không có quyền thay đổi file này.');
        }

        $this->service->setPrimary($file->id, $file->owner_type, $file->owner_id);

        return response()->json(['message' => 'Đã đặt làm ảnh chính']);
    }

    /**
     * Delete a file
     */
    public function destroy(Request $request, File $file)
    {
        $this->authorize('delete', $file);

        $user = $request->user();
        $isUploader = $file->uploaded_by === $user->id;
        $isAdmin = $user->isSuperAdmin() || $user->isOfficeAdmin();

        if (! $isUploader && ! $isAdmin) {
            abort(403, 'Bạn không có quyền xóa file này.');
        }

        $this->service->delete($file);

        return response()->json(['message' => 'Xóa file thành công']);
    }
}
