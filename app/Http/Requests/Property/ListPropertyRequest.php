<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class ListPropertyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],

            'category_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'integer'],
            'ward_id' => ['nullable', 'integer'],
            'project_id' => ['nullable', 'integer'],

            'price_min' => ['nullable', 'integer', 'min:0'],
            'price_max' => ['nullable', 'integer', 'min:0'],

            'area_min' => ['nullable', 'numeric', 'min:0'],
            'area_max' => ['nullable', 'numeric', 'min:0'],

            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'floor' => ['nullable', 'integer', 'min:0'],

            'direction' => ['nullable', 'string', 'in:Đông,Tây,Nam,Bắc,Đông Bắc,Đông Nam,Tây Bắc,Tây Nam'],

            'location_type' => ['nullable', 'string'],
            'shape' => ['nullable', 'string'],
            'legal_status' => ['nullable', 'string'],
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string'],

            'approval_status' => ['nullable', 'string', 'in:PENDING,APPROVED,REJECTED'],
            'visibility_status' => ['nullable', 'string', 'in:VISIBLE,HIDDEN,EXPIRED'],

            'sort' => ['nullable', 'string', 'in:created_at,price,area'],
            'order' => ['nullable', 'string', 'in:asc,desc'],

            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'q' => 'Từ khóa tìm kiếm',
            'category_id' => 'ID danh mục',
            'district_id' => 'ID quận/huyện',
            'ward_id' => 'ID phường/xã',
            'project_id' => 'ID dự án',
            'price_min' => 'Giá tối thiểu',
            'price_max' => 'Giá tối đa',
            'area_min' => 'Diện tích tối thiểu',
            'area_max' => 'Diện tích tối đa',
            'bedrooms' => 'Số phòng ngủ',
            'bathrooms' => 'Số phòng tắm',
            'floor' => 'Số tầng',
            'direction' => 'Hướng nhà',
            'approval_status' => 'Trạng thái duyệt',
            'visibility_status' => 'Trạng thái hiển thị',
            'sort' => 'Sắp xếp theo',
            'order' => 'Thứ tự sắp xếp',
            'limit' => 'Số lượng mỗi trang',
            'page' => 'Trang số',
        ];
    }
}
