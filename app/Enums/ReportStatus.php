<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ReportStatus: string implements HasLabel, HasColor, HasIcon
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';

    public function getLabel(): string
    {
        return match ($this) {
            self::OPEN => 'Mở',
            self::IN_PROGRESS => 'Đang xử lý',
            self::RESOLVED => 'Đã xử lý',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::IN_PROGRESS => 'info',
            self::RESOLVED => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::OPEN => 'heroicon-m-bell-alert',
            self::IN_PROGRESS => 'heroicon-m-arrow-path',
            self::RESOLVED => 'heroicon-m-check-badge',
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
        return $this === self::OPEN;
    }
}
