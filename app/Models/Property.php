<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\InvalidatesDashboardStats;

    protected $fillable = [
        'title',
        'description',
        'area_id',
        'project_id',
        'category_id',
        'address',
        'subdivision_id',
        'owner_name',
        'owner_phone',
        'price',
        'area',
        'width',
        'length',
        'road_width',
        'shape',
        'location_type',
        'legal_docs',
        'legal_status',
        'video_url',
        'amenities',
        'approval_status',
        'approval_note',
        'approved_by',
        'approved_at',
        'created_by',
        'bedrooms',
        'bathrooms',
        'direction',
        'floor',
        'year_built',
        'lat',
        'lng',
        'google_map_url',
    ];

    protected $casts = [
        'legal_docs' => 'array',
        'amenities' => 'array',
        'approved_at' => 'datetime',
        'price' => 'decimal:2',
        'area' => 'decimal:2',
        'width' => 'decimal:2',
        'length' => 'decimal:2',
        'lat' => 'decimal:8',
        'lng' => 'decimal:8',
        'bedrooms' => 'integer',
        'bathrooms' => 'integer',
        'year_built' => 'integer',
    ];

    // Scopes
    public function scopeWithinUserAreas(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->whereIn('area_id', $user->area_ids ?? [])
                ->orWhere('created_by', $user->id);
        });
    }

    // Relationships
    public function areaLocation(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'area_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subdivision(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'subdivision_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'owner');
    }

    /**
     * Get ordered files
     */
    public function orderedFiles(): MorphMany
    {
        return $this->morphMany(File::class, 'owner')->orderBy('order', 'asc');
    }

    /**
     * Get only property images (ordered)
     */
    public function images(): MorphMany
    {
        return $this->morphMany(File::class, 'owner')
            ->where('purpose', 'PROPERTY_IMAGE')
            ->orderBy('order', 'asc');
    }

    /**
     * Get primary image
     */
    public function primaryImage(): MorphOne
    {
        return $this->morphOne(File::class, 'owner')
            ->where('is_primary', true);
    }

    /**
     * Get primary image URL (accessor)
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primary = $this->primaryImage;
        if ($primary) {
            return $primary->url;
        }

        // Fallback to first image
        $firstImage = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();
        return $firstImage?->url;
    }

    /**
     * Get primary thumbnail URL (accessor)
     */
    public function getPrimaryThumbnailUrlAttribute(): ?string
    {
        $primary = $this->primaryImage;
        if ($primary) {
            return $primary->thumbnail_url ?? $primary->url;
        }

        // Fallback to first image
        $firstImage = $this->relationLoaded('images')
            ? $this->images->first()
            : $this->images()->first();
        return $firstImage?->thumbnail_url ?? $firstImage?->url;
    }

    public function phoneRequests(): HasMany
    {
        return $this->hasMany(OwnerPhoneRequest::class);
    }

    public function myApprovedPhoneRequest(): HasOne
    {
        return $this->hasOne(OwnerPhoneRequest::class)
            ->where('requester_id', auth()->id())
            ->where('status', 'APPROVED');
    }
    // Accessors
    public function getOwnerPhoneAttribute(?string $value): ?string
    {
        if (is_null($value)) return null;

        /** @var User|null $user */
        $user = auth()->user();

        // 1. System/Admin -> Allowed
        if (!$user || $user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return $value;
        }

        // 2. Creator -> Allowed
        if ((int)$this->created_by === (int)$user->id) {
            return $value;
        }

        // 3. Approved Request (Force Mask if not approved)
        if ($this->relationLoaded('myApprovedPhoneRequest') && $this->myApprovedPhoneRequest) {
            return $value;
        }

        // Default: MASKED
        return substr($value, 0, 3) . '****' . substr($value, -3);
    }

    public function getLegalDocsAttribute(mixed $value): mixed
    {
        if (is_null($value)) return null;

        /** @var User|null $user */
        $user = auth()->user();

        // 1. System/Admin -> Allowed
        if (!$user || $user->isSuperAdmin() || $user->isOfficeAdmin()) {
            return $value;
        }

        // 2. Creator -> Allowed
        if ($this->created_by === $user->id) {
            return $value;
        }

        // Default: HIDDEN
        return null;
    }
}
