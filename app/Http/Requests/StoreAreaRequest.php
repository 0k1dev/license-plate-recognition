<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAreaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'api_code' => ['nullable', 'integer'],
            'division_type' => ['nullable', 'string', 'max:50'],
            'codename' => ['nullable', 'string', 'max:100'],
            'phone_code' => ['nullable', 'integer'],
            'level' => ['required', 'string', 'in:province,district,ward'],
            'parent_id' => ['nullable', 'integer', 'exists:areas,id'],
            'path' => ['nullable', 'string', 'max:500'],
            'order' => ['nullable', 'integer', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
