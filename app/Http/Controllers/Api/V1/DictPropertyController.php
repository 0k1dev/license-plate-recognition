<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Settings\PropertyOptionsSettings;

class DictPropertyController extends Controller
{
    /**
     * Trả về tất cả options phục vụ form tạo/sửa BĐS trên app.
     *
     * GET /api/v1/dicts/property-options
     */
    public function options()
    {
        $options = $this->resolveOptions();

        return response()->json([
            'data' => [
                'amenities' => $options['amenities'],
                'directions' => $options['directions'],
                'shapes' => $options['shapes'],
                'location_types' => $options['location_types'],
                'legal_statuses' => $options['legal_statuses'],
            ],
        ]);
    }

    /**
     * Lấy Tiện ích từ Settings, fallback về config nếu settings chưa sẵn sàng.
     *
     * @return array{
     *   amenities: array,
     *   directions: array,
     *   shapes: array,
     *   location_types: array,
     *   legal_statuses: array
     * }
     */
    private function resolveOptions(): array
    {
        try {
            $settings = app(PropertyOptionsSettings::class)->toArray();
        } catch (\Throwable) {
            $settings = [];
        }

        return [
            'amenities' => $settings['amenities'] ?? config('property.amenities', []),
            'directions' => $settings['directions'] ?? config('property.directions', []),
            'shapes' => $settings['shapes'] ?? config('property.shapes', []),
            'location_types' => $settings['location_types'] ?? config('property.location_types', []),
            'legal_statuses' => $settings['legal_statuses'] ?? config('property.legal_statuses', []),
        ];
    }
}
