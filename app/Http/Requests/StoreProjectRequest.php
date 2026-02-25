<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:projects,slug'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
        ];
    }
}
