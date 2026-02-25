<?php

declare(strict_types=1);

namespace App\Http\Requests\Property;

use Illuminate\Foundation\Http\FormRequest;

class AdminListPropertyRequest extends ListPropertyRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Controller already checks authorize('viewAny', Property::class)
        // We can just return true here to rely on that, or duplicate check.
        // Returning true is fine as this request is used in a protected controller method.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = parent::rules();

        // Admin-specific additional filters
        $rules['created_by'] = ['nullable', 'integer', 'exists:users,id'];

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'created_by' => 'Người tạo',
        ]);
    }
}
