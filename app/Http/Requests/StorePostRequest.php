<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Post;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Post::class);
    }

    public function rules(): array
    {
        return [
            'property_id' => [
                'required',
                'exists:properties,id',
                function ($attribute, $value, $fail) {
                    $property = \App\Models\Property::find($value);
                    if (!$property) {
                        return;
                    }

                    if ($property->approval_status !== 'APPROVED') {
                        $fail('Bất động sản chưa được duyệt.');
                    }

                    /** @var \App\Models\User $user */
                    $user = $this->user();
                    if (!$user->hasRole('SUPER_ADMIN') && !$user->hasRole('OFFICE_ADMIN') && $property->created_by !== $user->id) {
                        $fail('Bạn không có quyền đăng tin cho Bất động sản này.');
                    }
                },
            ],
            'visible_until' => 'nullable|date|after:now',
        ];
    }
}
