<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'api_code',
        'division_type',
        'codename',
        'phone_code',
        'level',
        'parent_id',
        'path',
        'order',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'api_code' => 'integer',
        'phone_code' => 'integer',
        'order' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Parent area (self-referencing)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    /**
     * Children areas (self-referencing)
     */
    public function children(): HasMany
    {
        return $this->hasMany(Area::class, 'parent_id')
            ->orderBy('order')
            ->where('is_active', true);
    }

    /**
     * All children recursively
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }

    /**
     * Projects in this area
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Properties using this as area_id
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'area_id');
    }

    /**
     * Properties using this as district_id
     */
    public function propertiesAsDistrict(): HasMany
    {
        return $this->hasMany(Property::class, 'district_id');
    }

    /**
     * Properties using this as ward_id
     */
    public function propertiesAsWard(): HasMany
    {
        return $this->hasMany(Property::class, 'ward_id');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Only provinces
     */
    public function scopeProvinces(Builder $query): Builder
    {
        return $query->where('level', 'province')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope: Only districts
     */
    public function scopeDistricts(Builder $query): Builder
    {
        return $query->where('level', 'district')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope: Only wards
     */
    public function scopeWards(Builder $query): Builder
    {
        return $query->where('level', 'ward')
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope: Children of specific parent
     */
    public function scopeChildrenOf(Builder $query, ?int $parentId): Builder
    {
        return $query->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope: Districts of a province
     */
    public function scopeDistrictsOfProvince(Builder $query, int $provinceId): Builder
    {
        return $query->where('level', 'district')
            ->where('parent_id', $provinceId)
            ->where('is_active', true)
            ->orderBy('order');
    }

    /**
     * Scope: Wards of a district
     */
    public function scopeWardsOfDistrict(Builder $query, int $districtId): Builder
    {
        return $query->where('level', 'ward')
            ->where('parent_id', $districtId)
            ->where('is_active', true)
            ->orderBy('order');
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if this is a province
     */
    public function isProvince(): bool
    {
        return $this->level === 'province';
    }

    /**
     * Check if this is a district
     */
    public function isDistrict(): bool
    {
        return $this->level === 'district';
    }

    /**
     * Check if this is a ward
     */
    public function isWard(): bool
    {
        return $this->level === 'ward';
    }

    /**
     * Get full path as string
     */
    public function getFullPath(): string
    {
        return $this->path ?? $this->name;
    }

    // ==================== BOOT & CACHING ====================

    protected static function booted(): void
    {
        static::saved(function (Area $area) {
            self::clearAreaCache($area);
        });

        static::deleted(function (Area $area) {
            self::clearAreaCache($area);
        });
    }

    /**
     * Get Cached Provinces Options (ID => Name)
     * Cache 30 ngày (vì dữ liệu tỉnh thành rất ít thay đổi)
     */
    public static function getCachedProvincesOptions(): array
    {
        return cache()->remember('areas_provinces_options', 86400 * 30, function () {
            return self::where('level', 'province')
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Get Cached Subdivisions Options by Parent ID (ID => Name)
     */
    public static function getCachedSubdivisionsOptions(?int $parentId): array
    {
        if (!$parentId) {
            return [];
        }

        // Cache từng huyện theo parent_id
        return cache()->remember("areas_subdivisions_options_{$parentId}", 86400 * 30, function () use ($parentId) {
            return self::where('parent_id', $parentId)
                ->where('is_active', true)
                ->orderBy('order')
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    /**
     * Clear Cache helper
     */
    public static function clearAreaCache(?Area $area = null): void
    {
        // Luôn clear cache danh sách tỉnh
        cache()->forget('areas_provinces_options');

        if ($area) {
            if ($area->parent_id) {
                cache()->forget("areas_subdivisions_options_{$area->parent_id}");
            }
            cache()->forget("areas_subdivisions_options_{$area->id}");
        }
    }
}
