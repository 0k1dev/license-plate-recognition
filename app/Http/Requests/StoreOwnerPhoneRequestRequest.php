<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\OwnerPhoneRequest;
use App\Models\Post;
use App\Models\Property;
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
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $routeProperty = $this->route('property');
            $routePost = $this->route('post');

            $propertyId = $routeProperty instanceof Property
                ? (int) $routeProperty->id
                : ($routeProperty ? (int) $routeProperty : null);

            $post = null;
            if ($routePost instanceof Post) {
                $post = $routePost;
            } elseif ($routePost) {
                $post = Post::query()->find((int) $routePost);
            }

            if (!$propertyId && $post) {
                $propertyId = (int) $post->property_id;
            }

            $propertyId = (int) ($propertyId ?? 0);
            $userId = (int) auth()->id();

            if ($propertyId <= 0) {
                $validator->errors()->add(
                    'property',
                    'Không xác định được bất động sản cần yêu cầu.'
                );
                return;
            }

            $property = Property::query()->find($propertyId);
            if (!$property) {
                return;
            }

            if ((int) $property->created_by === $userId) {
                $validator->errors()->add(
                    'property_id',
                    'Bạn không thể gửi yêu cầu xem SĐT cho chính bất động sản của mình.'
                );
            }

            if ($post && (int) $post->property_id !== $propertyId) {
                $validator->errors()->add(
                    'post',
                    'Bài đăng không khớp với bất động sản yêu cầu.'
                );
            }

            if ($post && $post->status !== 'VISIBLE') {
                $validator->errors()->add(
                    'post',
                    'Chỉ có thể yêu cầu xem SĐT từ tin đăng đang hiển thị.'
                );
            }

            if (OwnerPhoneRequest::hasPendingRequest(
                $propertyId,
                $userId
            )) {
                $validator->errors()->add(
                    'property_id',
                    'Bạn đã có yêu cầu đang chờ duyệt cho BĐS này.'
                );
            }
        });
    }
}
