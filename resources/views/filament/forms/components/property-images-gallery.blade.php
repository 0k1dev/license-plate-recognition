@props([
'images' => collect(),
'propertyId' => null,
])
@php
use Illuminate\Support\Facades\Storage;
/** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
$publicDisk = Storage::disk('public');
@endphp

<div class="space-y-4">
    @if($images->isEmpty())
    <div class="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600">
        <x-heroicon-o-photo class="mx-auto h-12 w-12 text-gray-400" />
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Chưa có ảnh nào</p>
        <p class="text-xs text-gray-400 dark:text-gray-500">Hãy thêm ảnh bằng phần "Thêm ảnh mới" ở trên</p>
    </div>
    @else
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4" id="images-gallery-{{ $propertyId }}">
        @foreach($images as $image)
        <div class="relative group rounded-lg overflow-hidden border-2 {{ $image->is_primary ? 'border-primary-500 ring-2 ring-primary-200' : 'border-gray-200 dark:border-gray-700' }} bg-white dark:bg-gray-800 shadow-sm hover:shadow-md transition-all"
            data-image-id="{{ $image->id }}">

            {{-- Primary Badge --}}
            @if($image->is_primary)
            <div class="absolute top-2 left-2 z-10">
                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full bg-primary-500 text-white shadow">
                    <x-heroicon-m-star class="w-3 h-3" />
                    Ảnh chính
                </span>
            </div>
            @endif

            {{-- Image --}}
            <div class="aspect-square overflow-hidden">
                <img
                    src="{{ $publicDisk->url($image->path) }}"
                    alt="{{ $image->original_name }}"
                    class="w-full h-full object-cover cursor-pointer hover:scale-105 transition-transform duration-200"
                    data-url="{{ $publicDisk->url($image->path) }}"
                    onclick="window.open(this.dataset.url, '_blank')"
                    title="Click để xem ảnh gốc" />
            </div>

            {{-- Info & Actions --}}
            <div class="p-2 space-y-2">
                {{-- Filename --}}
                <p class="text-xs text-gray-600 dark:text-gray-400 truncate" title="{{ $image->original_name }}">
                    {{ $image->original_name }}
                </p>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between gap-1">
                    {{-- Set Primary --}}
                    @if(!$image->is_primary)
                    <button
                        type="button"
                        wire:click="$dispatch('setPrimaryImage', { imageId: {{ $image->id }} })"
                        class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1 text-xs font-medium rounded bg-yellow-50 text-yellow-700 hover:bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400 dark:hover:bg-yellow-900/50 transition-colors"
                        title="Đặt làm ảnh chính">
                        <x-heroicon-m-star class="w-3 h-3" />
                        Ảnh chính
                    </button>
                    @endif

                    {{-- Edit Name --}}
                    <button
                        type="button"
                        wire:click="$dispatch('editImageName', { imageId: {{ $image->id }}, currentName: '{{ addslashes($image->original_name) }}' })"
                        class="inline-flex items-center justify-center p-1.5 text-xs rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                        title="Sửa tên">
                        <x-heroicon-m-pencil class="w-3 h-3" />
                    </button>

                    {{-- Delete --}}
                    <button
                        type="button"
                        wire:click="$dispatch('deleteImage', { imageId: {{ $image->id }} })"
                        wire:confirm="Bạn có chắc muốn xoá ảnh này?"
                        class="inline-flex items-center justify-center p-1.5 text-xs rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition-colors"
                        title="Xoá ảnh">
                        <x-heroicon-m-trash class="w-3 h-3" />
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
        Tổng cộng: <strong>{{ $images->count() }}</strong> ảnh
        @if($images->where('is_primary', true)->count() > 0)
        • Đã chọn ảnh chính
        @else
        • <span class="text-warning-600">Chưa chọn ảnh chính</span>
        @endif
    </p>
    @endif
</div>