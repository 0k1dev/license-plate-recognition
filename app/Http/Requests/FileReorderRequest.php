<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FileReorderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array',
            'files.*.id' => 'required|exists:files,id',
            'files.*.order' => 'required|integer|min:0',
            'files.*.is_primary' => 'nullable|boolean',
        ];
    }
}
