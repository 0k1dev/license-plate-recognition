<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\Property;
use App\Models\User;
use App\Support\PropertyOptionResolver;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PropertyService
{
    public function __construct(
        protected FileService $fileService,
    ) {}

    /**
     * Tạo property mới (auto PENDING) + attach files.
     */
    public function create(
        User $user,
        array $data,
        array $propertyImages = [],
        array $legalDocFiles = []
    ): Property
    {
        $data['approval_status'] = 'PENDING';
        $data['created_by'] = $user->id;
        $propertyData = $this->extractPropertyAttributes($data);

        Log::info('PROPERTY_SERVICE_CREATE_START', [
            'user_id' => $user->id,
            'property_attributes' => array_keys($propertyData),
            'property_images_count' => count($propertyImages),
            'legal_doc_files_count' => count($legalDocFiles),
        ]);

        try {
            return DB::transaction(function () use ($propertyData, $data, $user, $propertyImages, $legalDocFiles): Property {
                $property = Property::create($propertyData);

                Log::info('PROPERTY_SERVICE_CREATE_MODEL_CREATED', [
                    'property_id' => $property->id,
                    'user_id' => $user->id,
                ]);

                $this->attachFiles($property, $data, $user, $propertyImages, $legalDocFiles);

                Log::info('PROPERTY_SERVICE_CREATE_ATTACH_COMPLETED', [
                    'property_id' => $property->id,
                    'property_images_count' => count($propertyImages),
                    'legal_doc_files_count' => count($legalDocFiles),
                ]);

                AuditLog::log('create_property', Property::class, $property->id);

                Log::info('PROPERTY_SERVICE_CREATE_SUCCESS', [
                    'property_id' => $property->id,
                    'user_id' => $user->id,
                ]);

                return $property;
            });
        } catch (\Throwable $e) {
            Log::error('PROPERTY_SERVICE_CREATE_FAILED', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'property_images_count' => count($propertyImages),
                'legal_doc_files_count' => count($legalDocFiles),
            ]);

            throw $e;
        }
    }

    /**
     * Cập nhật property + re-attach files nếu có.
     */
    public function update(
        Property $property,
        array $data,
        User $actor,
        array $propertyImages = [],
        array $legalDocFiles = []
    ): Property
    {
        $updateData = $this->extractPropertyAttributes($data);
        $legalPurpose = $this->resolveLegalDocPurpose($data, $property);

        DB::transaction(function () use ($property, $updateData, $data, $legalPurpose, $actor, $propertyImages, $legalDocFiles): void {
            if ($updateData !== []) {
                $property->update($updateData);
            }

            $this->attachPropertyImages(
                $property,
                (array) ($data['image_file_ids'] ?? []),
                $propertyImages,
                $actor,
                array_key_exists('image_file_ids', $data)
            );

            $this->attachLegalDocuments(
                $property,
                (array) ($data['legal_doc_file_ids'] ?? []),
                $legalDocFiles,
                $actor,
                $legalPurpose,
                array_key_exists('legal_doc_file_ids', $data)
            );

            AuditLog::log('update_property', Property::class, $property->id);
        });

        return $property->fresh();
    }

    /**
     * Approve property.
     */
    public function approve(Property $property, User $admin, ?string $note = null): void
    {
        if ($property->approval_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'approval_status' => ['Property is not pending approval.'],
            ]);
        }

        $property->approve($admin, $note);
    }

    /**
     * Reject property.
     */
    public function reject(Property $property, User $admin, string $reason): void
    {
        if ($property->approval_status !== 'PENDING') {
            throw ValidationException::withMessages([
                'approval_status' => ['Property is not pending approval.'],
            ]);
        }

        $property->reject($admin, $reason);
    }

    /**
     * Attach files (images + legal docs) cho property.
     */
    private function attachFiles(
        Property $property,
        array $data,
        User $actor,
        array $propertyImages = [],
        array $legalDocFiles = []
    ): void
    {
        $this->attachPropertyImages(
            $property,
            (array) ($data['image_file_ids'] ?? []),
            $propertyImages,
            $actor
        );

        $this->attachLegalDocuments(
            $property,
            (array) ($data['legal_doc_file_ids'] ?? []),
            $legalDocFiles,
            $actor,
            $this->resolveLegalDocPurpose($data, $property)
        );
    }

    private function resolveLegalDocPurpose(array $data, Property $property): string
    {
        $legalStatus = PropertyOptionResolver::normalizePurpose(
            $data['legal_status'] ?? $property->legal_status
        );

        if (PropertyOptionResolver::isLegalDocumentPurpose($legalStatus)) {
            return $legalStatus;
        }

        return PropertyOptionResolver::defaultLegalPurpose() ?? 'KHAC';
    }

    /**
     * Only allow files from current user pool, unassigned files, or files already on this property.
     * This prevents accidentally moving files from another property.
     *
     * @param array<int, mixed> $fileIds
     * @return \Illuminate\Database\Eloquent\Collection<int, File>
     */
    private function resolveAttachableFiles(array $fileIds, User $actor, Property $property, string $field): Collection
    {
        $ids = array_values(array_unique(array_map(static fn($id) => (int) $id, $fileIds)));

        if (empty($ids)) {
            return new Collection();
        }

        /** @var Collection<int, File> $files */
        $files = File::query()->whereIn('id', $ids)->get();
        $filesById = $files->keyBy('id');

        $missingIds = array_values(array_diff($ids, $filesById->keys()->map(fn($id) => (int) $id)->all()));
        if (!empty($missingIds)) {
            throw ValidationException::withMessages([
                $field => ['File không tồn tại: ' . implode(', ', $missingIds)],
            ]);
        }

        $forbiddenIds = [];
        $attachable = new \Illuminate\Database\Eloquent\Collection();

        foreach ($ids as $id) {
            /** @var File $file */
            $file = $filesById->get($id);

            $belongsToCurrentProperty = $file->owner_type === Property::class && (int) $file->owner_id === (int) $property->id;
            $belongsToCurrentUser = $file->owner_type === User::class && (int) $file->owner_id === (int) $actor->id;
            $isUnassigned = empty($file->owner_type) || empty($file->owner_id);

            if ($belongsToCurrentProperty || $belongsToCurrentUser || $isUnassigned) {
                $attachable->push($file);
                continue;
            }

            $forbiddenIds[] = $id;
        }

        if (!empty($forbiddenIds)) {
            throw ValidationException::withMessages([
                $field => ['Có ảnh bị trùng lặp với ảnh đã đăng trước đó, vui lòng thử lại!'],
            ]);
        }

        return $attachable;
    }

    /**
     * @param array<int, UploadedFile> $uploadedImages
     */
    private function attachPropertyImages(
        Property $property,
        array $imageFileIds,
        array $uploadedImages,
        User $actor,
        bool $replaceExisting = false
    ): void {
        if ($imageFileIds === [] && $uploadedImages === [] && ! $replaceExisting) {
            return;
        }

        Log::info('PROPERTY_ATTACH_IMAGES_START', [
            'property_id' => $property->id,
            'image_file_ids_count' => count($imageFileIds),
            'uploaded_images_count' => count($uploadedImages),
            'replace_existing' => $replaceExisting,
        ]);

        $imageFiles = $this->resolveAttachableFiles($imageFileIds, $actor, $property, 'image_file_ids');

        if ($replaceExisting) {
            File::where('owner_type', Property::class)
                ->where('owner_id', $property->id)
                ->where('purpose', 'PROPERTY_IMAGE')
                ->update([
                    'owner_type' => null,
                    'owner_id' => null,
                    'is_primary' => false,
                ]);
        }

        $this->attachExistingFilesToProperty(
            $property,
            $imageFiles,
            'PROPERTY_IMAGE',
            'PUBLIC'
        );

        if ($uploadedImages !== []) {
            $this->fileService->uploadMultiple(
                $uploadedImages,
                $actor,
                'PROPERTY_IMAGE',
                Property::class,
                (int) $property->id,
                'PUBLIC',
            );
        }

        $this->ensurePrimaryImage($property->id);

        Log::info('PROPERTY_ATTACH_IMAGES_DONE', [
            'property_id' => $property->id,
            'attached_existing_count' => $imageFiles->count(),
            'uploaded_images_count' => count($uploadedImages),
        ]);
    }

    /**
     * @param array<int, UploadedFile> $uploadedFiles
     */
    private function attachLegalDocuments(
        Property $property,
        array $legalDocFileIds,
        array $uploadedFiles,
        User $actor,
        string $legalPurpose,
        bool $replaceExisting = false
    ): void {
        if ($legalDocFileIds === [] && $uploadedFiles === [] && ! $replaceExisting) {
            return;
        }

        Log::info('PROPERTY_ATTACH_LEGAL_DOCS_START', [
            'property_id' => $property->id,
            'legal_doc_file_ids_count' => count($legalDocFileIds),
            'uploaded_legal_docs_count' => count($uploadedFiles),
            'legal_purpose' => $legalPurpose,
            'replace_existing' => $replaceExisting,
        ]);

        $legalFiles = $this->resolveAttachableFiles($legalDocFileIds, $actor, $property, 'legal_doc_file_ids');

        if ($replaceExisting) {
            File::where('owner_type', Property::class)
                ->where('owner_id', $property->id)
                ->whereIn('purpose', PropertyOptionResolver::legalStatusCodes())
                ->update([
                    'owner_type' => null,
                    'owner_id' => null,
                ]);
        }

        $this->attachExistingFilesToProperty(
            $property,
            $legalFiles,
            $legalPurpose,
            'PRIVATE'
        );

        if ($uploadedFiles !== []) {
            $this->fileService->uploadMultiple(
                $uploadedFiles,
                $actor,
                $legalPurpose,
                Property::class,
                (int) $property->id,
                'PRIVATE',
            );
        }

        Log::info('PROPERTY_ATTACH_LEGAL_DOCS_DONE', [
            'property_id' => $property->id,
            'attached_existing_count' => $legalFiles->count(),
            'uploaded_legal_docs_count' => count($uploadedFiles),
            'legal_purpose' => $legalPurpose,
        ]);
    }

    /**
     * @param Collection<int, File> $files
     */
    private function attachExistingFilesToProperty(
        Property $property,
        Collection $files,
        string $purpose,
        string $visibility
    ): void {
        if ($files->isEmpty()) {
            return;
        }

        $nextOrder = $this->getNextPropertyFileOrder($property->id, $purpose);

        foreach ($files as $file) {
            $this->moveFileBetweenDisks($file, $visibility);

            $updates = [
                'owner_type' => Property::class,
                'owner_id' => $property->id,
                'purpose' => $purpose,
                'visibility' => $visibility,
            ];

            if ($purpose === 'PROPERTY_IMAGE') {
                $updates['order'] = $nextOrder++;
                $updates['is_primary'] = false;
            }

            $file->update($updates);
        }
    }

    private function getNextPropertyFileOrder(int $propertyId, string $purpose): int
    {
        return (int) (
            File::query()
                ->where('owner_type', Property::class)
                ->where('owner_id', $propertyId)
                ->where('purpose', $purpose)
                ->max('order') ?? -1
        ) + 1;
    }

    private function extractPropertyAttributes(array $data): array
    {
        return Arr::only($data, (new Property())->getFillable());
    }

    private function ensurePrimaryImage(int $propertyId): void
    {
        $query = File::query()
            ->where('owner_type', Property::class)
            ->where('owner_id', $propertyId)
            ->where('purpose', 'PROPERTY_IMAGE');

        if (!(clone $query)->exists()) {
            return;
        }

        if ((clone $query)->where('is_primary', true)->exists()) {
            return;
        }

        $firstId = (clone $query)
            ->orderBy('order')
            ->orderBy('id')
            ->value('id');

        if ($firstId) {
            File::whereKey($firstId)->update(['is_primary' => true]);
        }
    }

    private function moveFileBetweenDisks(File $file, string $targetVisibility): void
    {
        $targetVisibility = strtoupper($targetVisibility);
        $fromDisk = strtoupper((string) $file->visibility) === 'PRIVATE' ? 'local' : 'public';
        $toDisk = $targetVisibility === 'PRIVATE' ? 'local' : 'public';

        if ($fromDisk === $toDisk) {
            return;
        }

        $this->movePathToDisk($file->path, $fromDisk, $toDisk);

        if ($file->thumbnail_path) {
            $this->movePathToDisk($file->thumbnail_path, $fromDisk, $toDisk);
        }
    }

    private function movePathToDisk(string $path, string $fromDisk, string $toDisk): void
    {
        if (!Storage::disk($fromDisk)->exists($path)) {
            return;
        }

        $stream = Storage::disk($fromDisk)->readStream($path);
        if ($stream === false) {
            return;
        }

        Storage::disk($toDisk)->put($path, $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        Storage::disk($fromDisk)->delete($path);
    }
}
