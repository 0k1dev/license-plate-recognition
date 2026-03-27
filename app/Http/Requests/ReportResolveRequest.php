<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportResolveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|string|in:HIDE_POST,LOCK_USER,WARN,NO_ACTION',
            'note' => 'nullable|string',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('note') && $this->filled('admin_note')) {
            $this->merge([
                'note' => $this->input('admin_note'),
            ]);
        }
    }
}
