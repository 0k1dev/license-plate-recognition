<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Post;
use App\Models\Report;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Report::class);
    }

    public function rules(): array
    {
        return [
            'post_id' => 'required|integer|exists:posts,id',
            'type' => 'required|string|max:50',
            'content' => 'required|string|max:1000',
            'files' => 'nullable|array|max:5',
            'files.*' => 'required|file|max:10240|mimes:jpeg,jpg,png,gif,webp,pdf,doc,docx',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('post_id')
            && $this->input('reportable_type') === Post::class
            && $this->filled('reportable_id')) {
            $this->merge([
                'post_id' => $this->input('reportable_id'),
            ]);
        }
    }

    public function messages(): array
    {
        return [
            'post_id.required' => 'Vui lòng chọn bài đăng cần báo cáo.',
            'post_id.exists' => 'Bài đăng được báo cáo không tồn tại.',
            'files.max' => 'Bạn chỉ có thể đính kèm tối đa 5 tệp bằng chứng.',
            'files.*.max' => 'Mỗi tệp bằng chứng phải nhỏ hơn hoặc bằng 10MB.',
            'files.*.mimes' => 'Định dạng bằng chứng được hỗ trợ: jpeg, jpg, png, gif, webp, pdf, doc, docx.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'error' => $validator->errors()->first(),
        ], 422));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json([
            'error' => 'Bạn không có quyền gửi báo cáo.',
        ], 403));
    }
}
