<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'area_ids',
        'is_locked',
        'dob',
        'cccd_image',
        'phone',
        'permanent_address',
        'current_address',
        'avatar_url',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'area_ids' => 'array',
            'is_locked' => 'boolean',
        ];
    }

    protected function areaIds(): Attribute
    {
        return Attribute::make(
            get: fn($value) => json_decode($value ?? '[]', true) ?? [],
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return !$this->is_locked;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('SUPER_ADMIN');
    }

    public function isOfficeAdmin(): bool
    {
        return $this->hasRole('OFFICE_ADMIN');
    }

    public function isFieldStaff(): bool
    {
        return $this->hasRole('FIELD_STAFF');
    }

    public function areas(): Collection
    {
        // Fake relationship if needed, or just relying on area_ids array
        // If real relation: return $this->belongsToMany(Area::class);
        // But schema uses json area_ids.
        // Helper to get Area models:
        return Area::whereIn('id', $this->area_ids ?? [])->get();
    }
}
