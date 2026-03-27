<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\PropertyOptionResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'purpose' => ['required', 'string', Rule::in(PropertyOptionResolver::uploadFilePurposes())],
            'visibility' => 'required|string|in:PUBLIC,PRIVATE',
            'owner_type' => 'nullable|string',
            'owner_id' => 'nullable|integer',
        ];
    }

    protected function prepareForValidation(): void
    {
        $purpose = PropertyOptionResolver::normalizePurpose($this->purpose);

        $this->merge([
            'purpose' => $purpose,
            'visibility' => is_string($this->visibility) ? strtoupper($this->visibility) : $this->visibility,
        ]);
    }
}
