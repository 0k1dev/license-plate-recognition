<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\OwnerPhoneRequest;
use Illuminate\Foundation\Http\FormRequest;

class StoreOwnerPhoneRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', OwnerPhoneRequest::class);
    }

    public function rules(): array
    {
        return [
            'property_id' => 'required|exists:properties,id',
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (OwnerPhoneRequest::hasPendingRequest(
                (int) $this->property_id,
                (int) auth()->id()
            )) {
                $validator->errors()->add(
                    'property_id',
                    'Bạn đã có yêu cầu đang chờ duyệt cho BĐS này.'
                );
            }
        });
    }
}
