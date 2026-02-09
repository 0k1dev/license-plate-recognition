<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_name',
        'path',
        'thumbnail_path',
        'mime_type',
        'size',
        'purpose',
        'visibility',
        'owner_type',
        'owner_id',
        'uploaded_by',
        'order',
        'is_primary',
    ];

    protected $casts = [
        'size' => 'integer',
        'order' => 'integer',
        'is_primary' => 'boolean',
    ];

    // ==================== SCOPES ====================

    /**
     * Scope to order by the 'order' column
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order', 'asc');
    }

    /**
     * Scope to get only property images
     */
    public function scopePropertyImages(Builder $query): Builder
    {
        return $query->where('purpose', 'PROPERTY_IMAGE');
    }

    /**
     * Scope to get only legal documents
     */
    public function scopeLegalDocs(Builder $query): Builder
    {
        return $query->where('purpose', 'LEGAL_DOC');
    }

    /**
     * Scope to get only public files
     */
    public function scopePublicOnly(Builder $query): Builder
    {
        return $query->where('visibility', 'PUBLIC');
    }

    // ==================== ACCESSORS ====================

    /**
     * Get full URL for the file
     */
    public function getUrlAttribute(): ?string
    {
        if (!$this->path) return null;

        $disk = $this->visibility === 'PRIVATE' ? 'local' : 'public';

        if ($disk === 'public') {
            return Storage::disk($disk)->url($this->path);
        }

        // For private files, return a route to download
        return route('files.download', $this->id);
    }

    /**
     * Get thumbnail URL
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->thumbnail_path) {
            return $this->url; // Fallback to original if no thumbnail
        }

        $disk = $this->visibility === 'PRIVATE' ? 'local' : 'public';

        if ($disk === 'public') {
            return Storage::disk($disk)->url($this->thumbnail_path);
        }

        return null; // Private thumbnails should use download route
    }

    /**
     * Check if file is an image
     */
    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    /**
     * Get human readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    // ==================== RELATIONSHIPS ====================

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
