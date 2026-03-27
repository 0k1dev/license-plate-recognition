<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\File;
use App\Models\User;
use App\Support\PropertyOptionResolver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class FileService
{
    protected const THUMBNAIL_WIDTH = 300;
    protected const THUMBNAIL_HEIGHT = 300;
    protected const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    protected const MAX_THUMBNAIL_SOURCE_PIXELS = 5000000;
    protected const MAX_THUMBNAIL_SOURCE_BYTES = 8 * 1024 * 1024;

    /**
     * Upload single file
     */
    public function upload(
        UploadedFile $uploadedFile,
        User $uploader,
        string $purpose,
        string $ownerType,
        int $ownerId,
        string $visibility = 'PUBLIC',
        int $order = 0,
        bool $isPrimary = false
    ): File {
        $purpose = $this->normalizePurpose($purpose);
        $disk = $visibility === 'PRIVATE' ? 'local' : 'public';
        $path = $uploadedFile->store($this->resolveUploadDirectory($purpose), $disk);
        $thumbnailPath = null;

        // Generate thumbnail for images
        $mimeType = $uploadedFile->getClientMimeType();
        if (in_array($mimeType, self::IMAGE_MIMES)) {
            $thumbnailPath = $this->generateThumbnail($uploadedFile, $path, $disk);
        }

        // If setting as primary, unset other primaries for same owner
        if ($isPrimary) {
            File::where('owner_type', $ownerType)
                ->where('owner_id', $ownerId)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        $file = File::create([
            'filename' => basename($path),
            'original_name' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'mime_type' => $mimeType,
            'size' => $uploadedFile->getSize(),
            'purpose' => $purpose,
            'visibility' => $visibility,
            'owner_type' => $ownerType,
            'owner_id' => $ownerId,
            'uploaded_by' => $uploader->id,
            'order' => $order,
            'is_primary' => $isPrimary,
        ]);

        AuditLog::log('upload_file', File::class, $file->id, [
            'purpose' => $purpose,
            'visibility' => $visibility
        ]);

        return $file;
    }

    /**
     * Normalize purpose aliases for backward compatibility.
     */
    protected function normalizePurpose(string $purpose): string
    {
        $purpose = PropertyOptionResolver::normalizePurpose($purpose) ?? $purpose;

        return match ($purpose) {
            // Backward compatibility for legacy purposes.
            'LEGAL_DOC', 'CCCD_FRONT', 'CCCD_BACK'
                => PropertyOptionResolver::defaultLegalPurpose() ?? 'KHAC',
            default => $purpose,
        };
    }

    /**
     * Resolve upload directory by purpose
     */
    protected function resolveUploadDirectory(string $purpose): string
    {
        if (PropertyOptionResolver::isLegalDocumentPurpose($purpose)) {
            return 'uploads/properties/documents';
        }

        return match ($purpose) {
            'PROPERTY_IMAGE' => 'uploads/properties/images',
            'AVATAR' => 'uploads/users/avatars',
            'REPORT_EVIDENCE' => 'uploads/reports/evidence',
            default => 'uploads/misc',
        };
    }

    /**
     * Upload multiple files at once
     * 
     * @param array<UploadedFile> $uploadedFiles
     * @return array<File>
     */
    public function uploadMultiple(
        array $uploadedFiles,
        User $uploader,
        string $purpose,
        string $ownerType,
        int $ownerId,
        string $visibility = 'PUBLIC',
        ?int $primaryIndex = null
    ): array {
        $files = [];
        $order = $this->getNextOrder($ownerType, $ownerId);

        foreach ($uploadedFiles as $index => $uploadedFile) {
            $isPrimary = ($primaryIndex !== null && $primaryIndex === $index);

            $files[] = $this->upload(
                $uploadedFile,
                $uploader,
                $purpose,
                $ownerType,
                $ownerId,
                $visibility,
                $order++,
                $isPrimary
            );
        }

        return $files;
    }

    /**
     * Generate thumbnail for image
     */
    protected function generateThumbnail(
        UploadedFile $uploadedFile,
        string $originalPath,
        string $disk
    ): ?string {
        // Tạm thời tăng memory limit để xử lý ảnh lớn
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            if (! $this->shouldGenerateThumbnail($uploadedFile)) {
                \Illuminate\Support\Facades\Log::info('Skipping thumbnail generation for large image', [
                    'file' => $uploadedFile->getClientOriginalName(),
                    'size' => $uploadedFile->getSize(),
                ]);

                ini_set('memory_limit', $originalMemoryLimit);
                return null;
            }

            $manager = new ImageManager(new Driver());
            $image = $manager->read($uploadedFile->getPathname());

            // Resize keeping aspect ratio
            $image->scaleDown(width: self::THUMBNAIL_WIDTH, height: self::THUMBNAIL_HEIGHT);

            // Generate thumbnail path
            $pathInfo = pathinfo($originalPath);
            $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];

            // Ensure directory exists
            $thumbnailDir = dirname($thumbnailPath);
            if (!Storage::disk($disk)->exists($thumbnailDir)) {
                Storage::disk($disk)->makeDirectory($thumbnailDir);
            }

            // Encode + save thumbnail (Intervention Image v3 no longer supports Image::save()).
            $extension = strtolower($pathInfo['extension'] ?? 'jpg');
            $encodedThumbnail = match ($extension) {
                'jpg', 'jpeg' => $image->toJpeg(80),
                'png' => $image->toPng(),
                'gif' => $image->toGif(),
                'webp' => $image->toWebp(80),
                default => $image->toJpeg(80),
            };

            Storage::disk($disk)->put($thumbnailPath, (string) $encodedThumbnail);

            ini_set('memory_limit', $originalMemoryLimit);
            return $thumbnailPath;
        } catch (\Exception $e) {
            ini_set('memory_limit', $originalMemoryLimit);
            \Illuminate\Support\Facades\Log::warning('Failed to generate thumbnail: ' . $e->getMessage());
            return null;
        }
    }

    protected function shouldGenerateThumbnail(UploadedFile $uploadedFile): bool
    {
        if (($uploadedFile->getSize() ?? 0) > self::MAX_THUMBNAIL_SOURCE_BYTES) {
            return false;
        }

        $imageInfo = @getimagesize($uploadedFile->getPathname());
        if ($imageInfo === false) {
            return true;
        }

        [$width, $height] = $imageInfo;

        return ($width * $height) <= self::MAX_THUMBNAIL_SOURCE_PIXELS;
    }

    /**
     * Get next order number for owner's files
     */
    public function getNextOrder(string $ownerType, int $ownerId): int
    {
        return File::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->max('order') + 1;
    }

    /**
     * Reorder files
     * 
     * @param array<int, int> $orderMap [file_id => new_order]
     */
    public function reorder(array $orderMap): void
    {
        foreach ($orderMap as $fileId => $newOrder) {
            File::where('id', $fileId)->update(['order' => $newOrder]);
        }
    }

    /**
     * Set primary image for owner
     */
    public function setPrimary(int $fileId, string $ownerType, int $ownerId): void
    {
        // Unset all current primaries
        File::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->update(['is_primary' => false]);

        // Set new primary
        File::where('id', $fileId)
            ->where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->update(['is_primary' => true]);
    }

    /**
     * Get primary image for owner
     */
    public function getPrimary(string $ownerType, int $ownerId): ?File
    {
        return File::where('owner_type', $ownerType)
            ->where('owner_id', $ownerId)
            ->where('is_primary', true)
            ->first();
    }

    /**
     * Delete file and its thumbnail
     */
    public function delete(File $file): bool
    {
        $disk = $file->visibility === 'PRIVATE' ? 'local' : 'public';

        // Delete thumbnail if exists
        if ($file->thumbnail_path && Storage::disk($disk)->exists($file->thumbnail_path)) {
            Storage::disk($disk)->delete($file->thumbnail_path);
        }

        // Delete original file
        if (Storage::disk($disk)->exists($file->path)) {
            Storage::disk($disk)->delete($file->path);
        }

        AuditLog::log('delete_file', File::class, $file->id);

        return $file->delete();
    }
}
