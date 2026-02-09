@php
/** @var \Illuminate\Filesystem\FilesystemAdapter $publicDisk */
$publicDisk = Storage::disk('public');
@endphp

<div class="flex items-center justify-center p-2 bg-gray-100 rounded-lg dark:bg-gray-800">
    @if($path)
    <img
        src="{{ $publicDisk->url($path) }}"
        alt="Preview"
        class="max-h-24 rounded-md object-cover cursor-pointer hover:opacity-80 transition-opacity"
        data-url="{{ $publicDisk->url($path) }}"
        onclick="window.open(this.dataset.url, '_blank')"
        title="Click để xem ảnh gốc" />
    @else
    <div class="text-gray-400 text-sm italic">Không có ảnh</div>
    @endif
</div>