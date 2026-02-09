<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Report::class);
    }

    public function rules(): array
    {
        return [
            'reportable_type' => 'required|string|in:App\Models\Post,App\Models\Property,App\Models\User',
            'reportable_id' => [
                'required',
                'integer',
                function ($attribute, $value, $fail) {
                    $type = $this->input('reportable_type');
                    if (!is_string($type) || !class_exists($type)) {
                        $fail('Đối tượng báo cáo không hợp lệ.');
                        return;
                    }

                    $exists = $type::query()->whereKey($value)->exists();
                    if (!$exists) {
                        $fail('Đối tượng báo cáo không tồn tại.');
                    }
                },
            ],
            'type' => 'required|string|max:50',
            'content' => 'required|string|max:1000',
        ];
    }
}
