<?php

declare(strict_types=1);

namespace App\Http\Requests\Post;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate các tham số lọc cho danh sách tin đăng.
 * Tất cả filter map 1-1 với các field của bảng properties (qua JOIN).
 */
class ListPostRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(array_filter([
            'street_name' => $this->input('street_name') ?? $this->input('streetName'),
        ], static fn($value) => $value !== null));
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Tìm kiếm tổng hợp
            'q'               => ['nullable', 'string', 'max:255'],
            'street_name'     => ['nullable', 'string', 'max:255'],

            // Lọc theo thuộc tính BĐS
            'category_id'     => ['nullable', 'integer', 'exists:categories,id'],
            'subdivision_id'  => ['nullable', 'integer'],
            'area_id'         => ['nullable', 'integer'],
            'project_id'      => ['nullable', 'integer', 'exists:projects,id'],

            // Khoảng giá & diện tích
            'price_min'       => ['nullable', 'integer', 'min:0'],
            'price_max'       => ['nullable', 'integer', 'min:0', 'gte:price_min'],
            'area_min'        => ['nullable', 'numeric', 'min:0'],
            'area_max'        => ['nullable', 'numeric', 'min:0', 'gte:area_min'],

            // Thông số căn hộ/nhà
            'bedrooms'        => ['nullable', 'integer', 'min:0'],
            'bathrooms'       => ['nullable', 'integer', 'min:0'],
            'floor'           => ['nullable', 'integer', 'min:0'],

            // Đặc điểm vị trí & pháp lý
            'direction'       => ['nullable', 'string', 'in:Đông,Tây,Nam,Bắc,Đông Bắc,Đông Nam,Tây Bắc,Tây Nam'],
            'location_type'   => ['nullable', 'string'],
            'shape'           => ['nullable', 'string'],
            'legal_status'    => ['nullable', 'string'],
            'amenities'       => ['nullable', 'array'],
            'amenities.*'     => ['string'],

            // Sắp xếp
            'sort'            => ['nullable', 'string', 'in:created_at,price,area,views_count'],
            'order'           => ['nullable', 'string', 'in:asc,desc'],

            // Pagination
            'limit'           => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'            => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'q'             => 'Từ khóa tìm kiếm',
            'street_name'   => 'Tên đường',
            'category_id'   => 'Danh mục',
            'subdivision_id' => 'Khu vực (phường/xã/thị trấn)',
            'area_id'       => 'Khu vực',
            'project_id'    => 'Dự án',
            'price_min'     => 'Giá tối thiểu',
            'price_max'     => 'Giá tối đa',
            'area_min'      => 'Diện tích tối thiểu',
            'area_max'      => 'Diện tích tối đa',
            'bedrooms'      => 'Số phòng ngủ',
            'bathrooms'     => 'Số phòng tắm',
            'floor'         => 'Số tầng',
            'direction'     => 'Hướng nhà',
            'sort'          => 'Sắp xếp theo',
            'order'         => 'Thứ tự sắp xếp',
            'limit'         => 'Số lượng mỗi trang',
        ];
    }
}
