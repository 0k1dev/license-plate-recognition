@php
use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-2">
    @if($files->isEmpty())
    <div class="text-center py-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-dashed border-gray-300 dark:border-gray-600">
        <x-heroicon-o-document class="mx-auto h-8 w-8 text-gray-400" />
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Chưa có tài liệu nào</p>
    </div>
    @else
    <div class="space-y-2">
        @foreach($files as $file)
        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
            {{-- Icon based on file type --}}
            <div class="flex-shrink-0">
                @if(str_starts_with($file->mime_type, 'image/'))
                <div class="w-12 h-12 rounded overflow-hidden">
                    <img
                        src="{{ Storage::disk('public')->url($file->path) }}"
                        alt="{{ $file->original_name }}"
                        class="w-full h-full object-cover" />
                </div>
                @elseif($file->mime_type === 'application/pdf')
                <div class="w-12 h-12 flex items-center justify-center bg-red-100 dark:bg-red-900/30 rounded">
                    <x-heroicon-o-document-text class="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                @else
                <div class="w-12 h-12 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded">
                    <x-heroicon-o-document class="w-6 h-6 text-gray-500 dark:text-gray-400" />
                </div>
                @endif
            </div>

            {{-- File Info --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    {{ $file->original_name }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ $file->human_size }} • {{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }}
                </p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-1">
                {{-- View/Download --}}
                <a
                    href="{{ Storage::disk('public')->url($file->path) }}"
                    target="_blank"
                    class="inline-flex items-center justify-center p-2 text-xs rounded bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50 transition-colors"
                    title="Xem/Tải về">
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                </a>

                {{-- Edit Name --}}
                <button
                    type="button"
                    wire:click="$dispatch('editFileName', { fileId: {{ $file->id }}, currentName: '{{ addslashes($file->original_name) }}' })"
                    class="inline-flex items-center justify-center p-2 text-xs rounded bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600 transition-colors"
                    title="Sửa tên">
                    <x-heroicon-m-pencil class="w-4 h-4" />
                </button>

                {{-- Delete --}}
                <button
                    type="button"
                    wire:click="$dispatch('deleteFile', { fileId: {{ $file->id }} })"
                    wire:confirm="Bạn có chắc muốn xoá tài liệu này?"
                    class="inline-flex items-center justify-center p-2 text-xs rounded bg-red-50 text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50 transition-colors"
                    title="Xoá">
                    <x-heroicon-m-trash class="w-4 h-4" />
                </button>
            </div>
        </div>
        @endforeach
    </div>

    <p class="text-xs text-gray-500 dark:text-gray-400">
        Tổng cộng: <strong>{{ $files->count() }}</strong> tài liệu
    </p>
    @endif
</div>