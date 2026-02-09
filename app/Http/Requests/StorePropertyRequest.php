<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Policy handled in Controller uses $this->authorize('create', Property::class)
        // Here we just return true or duplicate check if strict
        return true;
    }

    public function rules(): array
    {
        $currentYear = (int) date('Y');

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'area_id' => ['required', 'exists:areas,id'],
            'district_id' => ['nullable', 'exists:areas,id'],
            'ward_id' => ['nullable', 'exists:areas,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'area' => ['required', 'numeric', 'min:0'],

            // Kích thước đất
            'width' => ['nullable', 'numeric', 'min:0', 'max:10000'],
            'length' => ['nullable', 'numeric', 'min:0', 'max:10000'],

            // Vị trí và đường vào
            'road_width' => ['nullable', 'string', 'max:50'],
            'shape' => ['nullable', 'string', 'in:Vuông vức,Chữ nhật,Tóp hậu,Nở hậu,Không thường xuyên'],
            'location_type' => ['nullable', 'string', 'in:Mặt tiền,Ngõ hẻm,Trong ngõ,Trong khu dân cư'],

            // Thông tin chủ nhà
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_phone' => ['required', 'string', 'max:20'],

            // Media
            'video_url' => ['nullable', 'url', 'max:500'],

            // Tiện ích
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:100'],

            // File uploads
            'image_file_ids' => ['nullable', 'array'],
            'image_file_ids.*' => ['exists:files,id'],
            'legal_doc_file_ids' => ['nullable', 'array'],
            'legal_doc_file_ids.*' => ['exists:files,id'],

            // Chi tiết căn hộ
            'bedrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'bathrooms' => ['nullable', 'integer', 'min:0', 'max:50'],
            'direction' => ['nullable', 'string', 'in:Đông,Tây,Nam,Bắc,Đông Nam,Đông Bắc,Tây Nam,Tây Bắc'],
            'floor' => ['nullable', 'string', 'max:50'],
            'year_built' => ['nullable', 'integer', 'min:1900', "max:{$currentYear}"],

            // Tọa độ
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'google_map_url' => ['nullable', 'string', 'max:1000'],

            // Pháp lý
            'legal_status' => ['nullable', 'string', 'in:SO_DO,HOP_DONG_MB,VI_BANG,CHO_SO,KHAC'],
        ];
    }
}
