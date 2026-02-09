<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Project extends Model
{
    use HasFactory, \App\Traits\CacheableModel;

    protected $fillable = [
        'name',
        'slug',
        'area_id',
        'description',
        'image',
    ];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }
}
