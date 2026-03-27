<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:projects,slug,' . $this->route('project')?->id],
            'area_id' => [
                'sometimes',
                'integer',
                Rule::exists('areas', 'id')->where(
                    fn($query) => $query->where('level', 'province')->where('is_active', true)
                ),
            ],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
        ];
    }
}
