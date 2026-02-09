<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => 'nullable|in:PENDING,VISIBLE,HIDDEN,EXPIRED',
            'visible_until' => 'nullable|date',
        ];
    }
}
