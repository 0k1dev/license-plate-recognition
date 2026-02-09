<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum PostStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'PENDING';
    case VISIBLE = 'VISIBLE';
    case HIDDEN = 'HIDDEN';
    case EXPIRED = 'EXPIRED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => 'Chờ duyệt',
            self::VISIBLE => 'Hiển thị',
            self::HIDDEN => 'Đã ẩn',
            self::EXPIRED => 'Hết hạn',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::VISIBLE => 'success',
            self::HIDDEN => 'gray',
            self::EXPIRED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::VISIBLE => 'heroicon-m-eye',
            self::HIDDEN => 'heroicon-m-eye-slash',
            self::EXPIRED => 'heroicon-m-calendar-days',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $status) => [$status->value => $status->getLabel()])
            ->toArray();
    }

    public function isActive(): bool
    {
        return in_array($this, [self::VISIBLE, self::PENDING]);
    }

    public function canRenew(): bool
    {
        return in_array($this, [self::HIDDEN, self::EXPIRED]);
    }
}
