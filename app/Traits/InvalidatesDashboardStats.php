<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait InvalidatesDashboardStats
{
    public static function bootInvalidatesDashboardStats(): void
    {
        $clearStats = function () {
            Cache::increment('dashboard_stats_version');
        };

        static::saved($clearStats);
        static::deleted($clearStats);
    }
}
