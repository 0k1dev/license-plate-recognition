<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar
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



    public function canAccessPanel(Panel $panel): bool
    {
        return !$this->is_locked;
    }

    /**
     * Trả về URL thumbnail avatar để Filament hiển thị trên navbar.
     * Nếu chưa có thumb → tự tạo on-demand (khi user cũ chưa được xử lý).
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if (empty($this->avatar_url)) {
            return null;
        }

        // Nếu là URL tuyệt đối (external), dùng thẳng — không thể resize
        if (str_starts_with($this->avatar_url, 'http')) {
            return $this->avatar_url;
        }

        // Dùng thumbnail 120×120 thay vì ảnh gốc
        return app(\App\Services\ImageService::class)
            ->thumbnailUrl($this->avatar_url, 'avatar');
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
