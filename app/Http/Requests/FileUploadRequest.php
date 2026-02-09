<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:10240', // 10MB
            'purpose' => 'required|string|in:PROPERTY_IMAGE,AVATAR,CCCD_FRONT,CCCD_BACK,LEGAL_DOC,REPORT_EVIDENCE',
            'visibility' => 'required|string|in:PUBLIC,PRIVATE',
            'owner_type' => 'nullable|string',
            'owner_id' => 'nullable|integer',
        ];
    }
}
