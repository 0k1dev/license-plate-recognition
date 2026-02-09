<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel, HasColor, HasIcon
{
    case NEW = 'NEW';
    case RESOLVED = 'RESOLVED';
    case REJECTED = 'REJECTED';

    public function getLabel(): string
    {
        return match ($this) {
            self::NEW => 'Mới',
            self::RESOLVED => 'Đã xử lý',
            self::REJECTED => 'Từ chối',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::NEW => 'warning',
            self::RESOLVED => 'success',
            self::REJECTED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::NEW => 'heroicon-m-bell-alert',
            self::RESOLVED => 'heroicon-m-check-badge',
            self::REJECTED => 'heroicon-m-no-symbol',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn(self $status) => [$status->value => $status->getLabel()])
            ->toArray();
    }

    public function isPending(): bool
    {
        return $this === self::NEW;
    }
}
