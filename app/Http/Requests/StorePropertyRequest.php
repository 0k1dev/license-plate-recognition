<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\PropertyOptionResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $subdivisionId = $this->input('subdivision_id')
            ?? $this->input('subdivisionId')
            ?? $this->input('ward_id')
            ?? $this->input('wardId')
            ?? $this->input('district_id')
            ?? $this->input('districtId');

        $mappings = [
            'categoryId' => 'category_id',
            'areaId' => 'area_id',
            'projectId' => 'project_id',
            'streetName' => 'street_name',
            'imageFileIds' => 'image_file_ids',
            'legalDocFileIds' => 'legal_doc_file_ids',
            'googleMapUrl' => 'google_map_url',
            'sourcePhone' => 'source_phone',
            'sourceCode' => 'source_code',
        ];

        foreach ($mappings as $camel => $snake) {
            if ($this->has($camel) && !$this->has($snake)) {
                $this->merge([$snake => $this->input($camel)]);
            }
        }

        if ($subdivisionId !== null && !$this->has('subdivision_id')) {
            $this->merge(['subdivision_id' => $subdivisionId]);
        }

        if (! $this->hasFile('property_images') && $this->hasFile('propertyImages')) {
            $this->files->set('property_images', $this->file('propertyImages'));
        }

        if (! $this->hasFile('legal_doc_files') && $this->hasFile('legal_doc_file')) {
            $files = $this->file('legal_doc_file');
            $this->files->set('legal_doc_files', is_array($files) ? $files : [$files]);
        }

        if (! $this->hasFile('legal_doc_files') && $this->hasFile('legalDocFiles')) {
            $this->files->set('legal_doc_files', $this->file('legalDocFiles'));
        }
    }

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
            'subdivision_id' => ['nullable', 'exists:areas,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'address' => ['required', 'string', 'max:255'],
            'street_name' => ['nullable', 'string', 'max:255'],
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
            'source_phone' => ['nullable', 'string', 'max:20'],
            'source_code' => ['nullable', 'string', 'max:50'],

            // Media
            'video_url' => ['nullable', 'url', 'max:255'],

            // Tiện ích
            'amenities' => ['nullable', 'array'],
            'amenities.*' => ['string', 'max:100'],

            // File uploads
            'image_file_ids' => ['nullable', 'array'],
            'image_file_ids.*' => ['exists:files,id'],
            'legal_doc_file_ids' => ['nullable', 'array'],
            'legal_doc_file_ids.*' => ['exists:files,id'],
            'property_images' => ['nullable', 'array', 'max:20'],
            'property_images.*' => ['required', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp'],
            'legal_doc_files' => ['nullable', 'array', 'max:10'],
            'legal_doc_files.*' => ['required', 'file', 'max:10240', 'mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx'],

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
            'legal_status' => ['nullable', 'string', Rule::in(PropertyOptionResolver::legalStatusCodes())],
        ];
    }

    public function messages(): array
    {
        return [
            'property_images.max' => 'Tối đa 20 ảnh cho một bất động sản.',
            'property_images.*.max' => 'Mỗi ảnh phải nhỏ hơn hoặc bằng 10MB.',
            'property_images.*.mimes' => 'Ảnh bất động sản chỉ hỗ trợ jpeg, jpg, png, gif, webp.',
            'video_url.max' => 'Link video tối đa 255 ký tự. Không truyền link Google Maps vào trường video_url.',
            'legal_doc_files.max' => 'Tối đa 10 tệp pháp lý cho một bất động sản.',
            'legal_doc_files.*.max' => 'Mỗi tệp pháp lý phải nhỏ hơn hoặc bằng 10MB.',
            'legal_doc_files.*.mimes' => 'Tệp pháp lý chỉ hỗ trợ jpeg, jpg, png, gif, webp, pdf, doc, docx.',
        ];
    }
}
