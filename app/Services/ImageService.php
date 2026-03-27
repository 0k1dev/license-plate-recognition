<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Laravel\Facades\Image;

class ImageService
{
    /**
     * Các preset thumbnail thường dùng.
     * key => [width, height, quality]
     */
    public const PRESETS = [
        'avatar'    => ['width' => 120,  'height' => 120,  'quality' => 85],
        'thumb'     => ['width' => 300,  'height' => 200,  'quality' => 80],
        'medium'    => ['width' => 800,  'height' => 600,  'quality' => 85],
        'card'      => ['width' => 400,  'height' => 300,  'quality' => 80],
    ];

    /**
     * Tạo thumbnail từ path ảnh gốc đã lưu trong storage.
     *
     * @param  string  $originalPath  Relative path trong disk (vd: avatars/abc.jpg)
     * @param  string  $preset        Một trong các key của PRESETS
     * @param  string  $disk          Disk storage (mặc định: public)
     * @return string|null            Relative path thumbnail đã lưu, hoặc null nếu lỗi
     */
    public function makeThumbnail(
        string $originalPath,
        string $preset = 'avatar',
        string $disk = 'public'
    ): ?string {
        if (!Storage::disk($disk)->exists($originalPath)) {
            return null;
        }

        // Tạm thời tăng memory limit để xử lý ảnh lớn
        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        $config = self::PRESETS[$preset] ?? self::PRESETS['thumb'];

        try {
            $imageContent = Storage::disk($disk)->get($originalPath);

            $image = Image::read($imageContent);

            // Cover crop: cắt đúng tỉ lệ, không méo ảnh
            $image->cover($config['width'], $config['height']);

            // Đường dẫn thumb: thêm prefix "thumbnails/preset/" vào thư mục gốc
            $thumbPath = $this->buildThumbPath($originalPath, $preset);

            Storage::disk($disk)->put(
                $thumbPath,
                $image->toJpeg($config['quality'])->toString()
            );

            // Restore memory limit
            ini_set('memory_limit', $originalMemoryLimit);

            return $thumbPath;
        } catch (\Throwable $e) {
            // Restore memory limit on error
            ini_set('memory_limit', $originalMemoryLimit);

            \Illuminate\Support\Facades\Log::warning("ImageService: Không thể tạo thumbnail", [
                'path'   => $originalPath,
                'preset' => $preset,
                'error'  => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Trả về URL công khai của thumbnail.
     * Nếu thumb chưa tồn tại, tự động tạo rồi trả URL.
     */
    public function thumbnailUrl(
        string $originalPath,
        string $preset = 'avatar',
        string $disk = 'public'
    ): ?string {
        $thumbPath = $this->buildThumbPath($originalPath, $preset);

        // Tạo thumb nếu chưa có
        if (!Storage::disk($disk)->exists($thumbPath)) {
            $thumbPath = $this->makeThumbnail($originalPath, $preset, $disk);
        }

        if (!$thumbPath) {
            return null;
        }

        // Storage::disk()->url() hoạt động đúng runtime; cast qua closure để tránh IDE warning
        /** @var \Illuminate\Filesystem\FilesystemAdapter $fs */
        $fs = Storage::disk($disk);
        return $fs->url($thumbPath);
    }

    /**
     * Xoá thumbnail khi ảnh gốc bị xoá.
     */
    public function deleteThumbnails(string $originalPath, string $disk = 'public'): void
    {
        foreach (array_keys(self::PRESETS) as $preset) {
            $thumbPath = $this->buildThumbPath($originalPath, $preset);
            if (Storage::disk($disk)->exists($thumbPath)) {
                Storage::disk($disk)->delete($thumbPath);
            }
        }
    }

    /**
     * Build path thumbnail theo quy tắc:
     * "avatars/abc.jpg" + preset "avatar" → "thumbnails/avatar/avatars/abc.jpg"
     */
    private function buildThumbPath(string $originalPath, string $preset): string
    {
        return "thumbnails/{$preset}/{$originalPath}";
    }
}
