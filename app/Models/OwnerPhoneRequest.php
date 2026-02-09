<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerPhoneRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'requester_id',
        'status',
        'reason',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public static function hasPendingRequest(int $propertyId, int $requesterId): bool
    {
        return static::where('property_id', $propertyId)
            ->where('requester_id', $requesterId)
            ->where('status', 'PENDING')
            ->exists();
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
