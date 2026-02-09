<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileStoreMultipleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'required|file|max:10240|mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx',
            'purpose' => 'required|string',
            'owner_type' => 'nullable|string',
            'owner_id' => 'nullable|integer',
            'visibility' => 'nullable|in:PUBLIC,PRIVATE',
            'primary_index' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Vui lòng chọn ít nhất 1 file.',
            'files.max' => 'Tối đa 20 files mỗi lần upload.',
            'files.*.max' => 'Mỗi file không được vượt quá 10MB.',
            'files.*.mimes' => 'Chỉ chấp nhận file ảnh (jpeg, jpg, png, gif, webp) hoặc tài liệu (pdf, doc, docx).',
        ];
    }
}
