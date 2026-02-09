<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\PostStatus;
use App\Models\Post;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PostActivityChart extends ChartWidget
{
    protected static ?string $heading = 'Hoạt động bài đăng (30 ngày)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 1;

    protected static ?string $pollingInterval = '300s';

    protected static ?string $maxHeight = '280px';

    private const CACHE_TTL = 120;

    protected function getData(): array
    {
        $user = Auth::user();
        $cacheKey = "post_activity_chart_{$user?->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $isFieldStaff = $user?->hasRole('FIELD_STAFF');
            $areaIds = $isFieldStaff ? $user->area_ids : null;

            // Visible posts trend
            $visibleQuery = Post::query()->where('status', PostStatus::VISIBLE->value);
            if ($areaIds) {
                $visibleQuery->whereHas('property', fn($q) => $q->whereIn('area_id', $areaIds));
            }

            $visibleData = Trend::query($visibleQuery)
                ->between(start: now()->subDays(29), end: now())
                ->perDay()
                ->count();

            // Hidden/Expired posts trend
            $hiddenQuery = Post::query()->whereIn('status', [PostStatus::HIDDEN->value, PostStatus::EXPIRED->value]);
            if ($areaIds) {
                $hiddenQuery->whereHas('property', fn($q) => $q->whereIn('area_id', $areaIds));
            }

            $hiddenData = Trend::query($hiddenQuery)
                ->between(start: now()->subDays(29), end: now())
                ->perDay()
                ->count();

            // Chỉ hiển thị 15 labels để tránh quá dài
            $labels = $visibleData->map(function (TrendValue $value, $index) {
                return $index % 2 === 0 ? date('d/m', strtotime($value->date)) : '';
            });

            return [
                'datasets' => [
                    [
                        'label' => 'Đang hiển thị',
                        'data' => $visibleData->map(fn(TrendValue $value) => $value->aggregate),
                        'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                        'borderColor' => 'rgb(16, 185, 129)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'tension' => 0.4,
                        'pointRadius' => 0,
                        'pointHoverRadius' => 6,
                        'pointHoverBackgroundColor' => 'rgb(16, 185, 129)',
                    ],
                    [
                        'label' => 'Đã ẩn/Hết hạn',
                        'data' => $hiddenData->map(fn(TrendValue $value) => $value->aggregate),
                        'backgroundColor' => 'rgba(107, 114, 128, 0.1)',
                        'borderColor' => 'rgb(107, 114, 128)',
                        'borderWidth' => 2,
                        'fill' => true,
                        'tension' => 0.4,
                        'pointRadius' => 0,
                        'pointHoverRadius' => 6,
                        'pointHoverBackgroundColor' => 'rgb(107, 114, 128)',
                    ],
                ],
                'labels' => $labels,
            ];
        });
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                    'labels' => [
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'padding' => 16,
                        'font' => [
                            'size' => 12,
                            'weight' => 'bold',
                        ],
                    ],
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'grid' => [
                        'display' => true,
                        'drawBorder' => false,
                    ],
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
                'x' => [
                    'grid' => [
                        'display' => false,
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }
}
