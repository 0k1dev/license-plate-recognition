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
     * List files (Admin only)
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', File::class);

        $query = File::query()->latest();
        $this->applyListFilters($query, $request);

        $limit = min((int) $request->input('limit', 20), 100);
        $files = $query->paginate($limit)->withQueryString();

        return \App\Http\Resources\FileResource::collection($files);
    }

    /**
     * List my uploaded files
     */
    public function me(Request $request)
    {
        $query = File::query()
            ->where('uploaded_by', $request->user()->id)
            ->latest();

        $this->applyListFilters($query, $request);

        $files = $query->get();

        return \App\Http\Resources\FileResource::collection($files);
    }

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
        $this->service->upload(
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
        ], 201);
    }

    /**
     * Download a file
     */
    public function download(Request $request, File $file)
    {
        // Chấp nhận nếu có Chữ ký hợp lệ (Signed URL) HOẶC đã login (Bearer/Session)
        $hasAuth = $request->user() !== null || $request->hasValidSignature();

        // Nếu file PRIVATE, kiểm tra quyền tối thiểu
        if ($file->visibility === 'PRIVATE') {
            if (!$hasAuth) {
                abort(401, 'Unauthenticated or Invalid Signature.');
            }

            // Nếu không có chữ ký, ta check Policy/Logic thông thường
            if (!$request->hasValidSignature()) {
                $user = $request->user();
                $isUploader = (int) $file->uploaded_by === (int) $user->id;
                $isAdmin = $user->isAdmin();
                
                // Check if user is property owner
                $isOwner = false;
                if ($file->owner_type === 'App\Models\Property') {
                    $property = \App\Models\Property::find($file->owner_id);
                    $isOwner = $property && (int) $property->created_by === (int) $user->id;
                }

                if (!$isUploader && !$isAdmin && !$isOwner) {
                    abort(403, 'Bạn không có quyền tải file này.');
                }
            }
        }

        $disk = $file->visibility === 'PRIVATE' ? 'local' : 'public';

        if (! Storage::disk($disk)->exists($file->path)) {
            abort(404, 'File không tồn tại.');
        }

        // Ưu tiên hiện trực tiếp (inline) cho ảnh nếu không yêu cầu tải về cụ thể
        $forceDownload = $request->boolean('download');
        $isImage = str_starts_with($file->mime_type ?? '', 'image/');

        if ($request->boolean('inline') || ($isImage && !$forceDownload)) {
            return Storage::disk($disk)->response(
                $file->path,
                $file->original_name,
                [
                    'Content-Type' => $file->mime_type ?? 'application/octet-stream',
                    'Content-Disposition' => 'inline; filename="' . addslashes($file->original_name) . '"'
                ]
            );
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

    private function applyListFilters(\Illuminate\Database\Eloquent\Builder $query, Request $request): void
    {
        if ($request->filled('purpose')) {
            $query->where('purpose', $request->string('purpose')->toString());
        }

        if ($request->filled('visibility')) {
            $query->where('visibility', strtoupper($request->string('visibility')->toString()));
        }

        if ($request->filled('owner_type')) {
            $query->where('owner_type', $request->string('owner_type')->toString());
        }

        if ($request->filled('owner_id')) {
            $query->where('owner_id', (int) $request->input('owner_id'));
        }

        if ($request->filled('is_primary')) {
            $query->where('is_primary', $request->boolean('is_primary'));
        }
    }
}
