<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheableModel
{
    /**
     * Thời gian cache mặc định (giây) - Mặc định 1 ngày
     */
    public int $cacheFor = 86400;

    /**
     * Boot the trait.
     */
    public static function bootCacheableModel(): void
    {
        static::saved(function ($model) {
            $model->flushCache();
        });

        static::deleted(function ($model) {
            $model->flushCache();
        });
    }

    /**
     * Get unique cache key prefix for this model
     */
    protected function getCachePrefix(): string
    {
        return 'model_cache_' . $this->getTable();
    }

    /**
     * Flush all cache dealing with this model
     */
    public function flushCache(): void
    {
        // Xóa cache tag nếu driver hỗ trợ (Redis/Memcached)
        if (Cache::supportsTags()) {
            Cache::tags($this->getTable())->flush();
        } else {
            // Với driver file/database, ta không thể xóa theo tag.
            // Giải pháp đơn giản: Xóa cache key cụ thể nếu biết, 
            // hoặc chấp nhận cache chỉ hết hạn theo thời gian.
            // Tuy nhiên, để đảm bảo tính đúng đắn, ta có thể clear toàn bộ cache ứng dụng 
            // (hơi cực đoan) hoặc sử dụng versioning key (nhưng phức tạp).

            // Ở đây tôi sẽ implement một cơ chế "Version" đơn giản lưu trong cache
            // Mỗi lần update data -> Tăng version -> Các key cũ tự động vô hiệu hóa logic.
            Cache::increment($this->getCachePrefix() . '_version');
        }
    }

    /**
     * Lấy version hiện tại của bảng này
     */
    protected static function getCacheVersion(string $table): int
    {
        return (int) Cache::get('model_cache_' . $table . '_version', 1);
    }

    /**
     * Helper: Lấy danh sách Options cho Select (có Cache)
     * Thường dùng cho Filament: ->options(Model::getCachedOptions())
     */
    public static function getCachedOptions(
        string $column = 'name',
        string $key = 'id',
        ?callable $queryCallback = null
    ): array {
        $instance = new static();
        $table = $instance->getTable();
        $version = self::getCacheVersion($table);
        $cacheKey = "options_{$table}_v{$version}_{$column}_{$key}";

        return Cache::remember($cacheKey, $instance->cacheFor ?? 86400, function () use ($instance, $column, $key, $queryCallback) {
            $query = $instance->newQuery();

            if ($instance->timestamps && in_array('order', $instance->getFillable())) {
                $query->orderBy('order');
            } elseif ($instance->timestamps) {
                $query->latest();
            }

            if ($queryCallback) {
                $queryCallback($query);
            }

            return $query->pluck($column, $key)->toArray();
        });
    }

    /**
     * Helper: Lấy toàn bộ records (có Cache)
     */
    public static function getCachedAll(): \Illuminate\Database\Eloquent\Collection
    {
        $instance = new static();
        $table = $instance->getTable();
        $version = self::getCacheVersion($table);
        $cacheKey = "all_{$table}_v{$version}";

        return Cache::remember($cacheKey, $instance->cacheFor ?? 86400, function () use ($instance) {
            return $instance->all();
        });
    }
}
