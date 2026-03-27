<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes, \App\Traits\InvalidatesDashboardStats;

    protected $fillable = [
        'property_id',
        'status',
        'visible_until',
        'renew_count',
        'views_count',
        'created_by',
    ];

    protected $casts = [
        'visible_until' => 'datetime',
        'views_count'   => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** Danh sách lịch sử xem (PostView records) */
    public function postViews(): HasMany
    {
        return $this->hasMany(PostView::class);
    }

    /** Users đã xem tin này */
    public function viewedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_views')
            ->withPivot('viewed_at')
            ->orderByPivot('viewed_at', 'desc');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
