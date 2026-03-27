<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\PropertyOptionResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'purpose' => ['required', 'string', Rule::in(PropertyOptionResolver::uploadFilePurposes())],
            'owner_type' => 'nullable|string',
            'owner_id' => 'nullable|integer',
            'visibility' => 'nullable|in:PUBLIC,PRIVATE',
            'primary_index' => 'nullable|integer|min:0',
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

    public function messages(): array
    {
        return [
            'purpose.in' => 'Purpose is invalid.',
            'files.required' => 'Please select at least 1 file.',
            'files.max' => 'Maximum 20 files per upload.',
            'files.*.max' => 'Each file must be <= 10MB.',
            'files.*.mimes' => 'Allowed file types: jpeg, jpg, png, gif, webp, pdf, doc, docx.',
        ];
    }
}
