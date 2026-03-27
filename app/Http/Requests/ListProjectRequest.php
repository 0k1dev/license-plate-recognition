<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->filled('area_id') && $this->filled('areaId')) {
            $this->merge(['area_id' => $this->input('areaId')]);
        }
    }

    public function rules(): array
    {
        return [
            'area_id' => [
                'required',
                'integer',
                Rule::exists('areas', 'id')->where(
                    fn($query) => $query->where('level', 'province')->where('is_active', true)
                ),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'area_id' => 'ID khu vực',
        ];
    }
}
