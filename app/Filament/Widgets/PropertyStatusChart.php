<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\ApprovalStatus;
use App\Models\Property;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PropertyStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Phân bổ trạng thái BĐS';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 1;

    protected static ?string $pollingInterval = '300s';

    protected static ?string $maxHeight = '280px';

    private const CACHE_TTL = 120;

    protected function getData(): array
    {
        $user = Auth::user();
        $cacheKey = "property_status_chart_{$user?->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            $query = Property::query();

            if ($user?->hasRole('FIELD_STAFF') && $user->area_ids) {
                $query->whereIn('area_id', $user->area_ids);
            }

            $pending = (clone $query)->where('approval_status', ApprovalStatus::PENDING->value)->count();
            $approved = (clone $query)->where('approval_status', ApprovalStatus::APPROVED->value)->count();
            $rejected = (clone $query)->where('approval_status', ApprovalStatus::REJECTED->value)->count();

            return [
                'datasets' => [
                    [
                        'label' => 'Số lượng BĐS',
                        'data' => [$pending, $approved, $rejected],
                        'backgroundColor' => [
                            'rgba(251, 191, 36, 0.8)',  // Amber for pending
                            'rgba(16, 185, 129, 0.8)',  // Emerald for approved
                            'rgba(239, 68, 68, 0.8)',   // Red for rejected
                        ],
                        'borderColor' => [
                            'rgb(251, 191, 36)',
                            'rgb(16, 185, 129)',
                            'rgb(239, 68, 68)',
                        ],
                        'borderWidth' => 2,
                        'hoverOffset' => 12,
                    ],
                ],
                'labels' => [
                    "Chờ duyệt ({$pending})",
                    "Đã duyệt ({$approved})",
                    "Từ chối ({$rejected})",
                ],
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
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
                    'backgroundColor' => 'rgba(0, 0, 0, 0.8)',
                    'titleFont' => ['size' => 14, 'weight' => 'bold'],
                    'bodyFont' => ['size' => 13],
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
            'cutout' => '65%',
            'animation' => [
                'animateRotate' => true,
                'animateScale' => true,
            ],
        ];
    }
}
