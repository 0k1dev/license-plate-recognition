<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthUpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'phone' => 'nullable|string|max:20',
            'dob' => 'nullable|date',
            'permanent_address' => 'nullable|string|max:255',
            'current_address' => 'nullable|string|max:255',
            'avatar_url' => 'nullable|string|max:2048',
            'avatar' => 'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }
}
