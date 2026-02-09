@php
use Illuminate\Support\Facades\Storage;
@endphp

<div class="space-y-4" x-data="{ isDragging: false }">
    {{-- Upload Section - Compact Dropzone --}}
    <div
        class="relative"
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="isDragging = false">
        {{-- Loading Overlay --}}
        <div wire:loading wire:target="newFiles" class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 rounded-xl backdrop-blur-sm">
            <div class="flex items-center gap-3">
                <div class="w-6 h-6 border-3 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Đang xử lý...</span>
            </div>
        </div>

        <label for="upload-docs-{{ $property->id }}" class="block cursor-pointer">
            <div
                class="relative overflow-hidden rounded-xl border-2 border-dashed transition-all duration-300 p-5"
                :class="isDragging 
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20' 
                    : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 hover:border-primary-400 hover:bg-primary-50/50 dark:hover:bg-primary-900/10'">
                <div class="flex items-center gap-4">
                    {{-- Icon --}}
                    <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-800/50 dark:to-primary-700/50 flex items-center justify-center shadow-lg shadow-primary-500/10">
                        <x-heroicon-o-document-arrow-up class="w-6 h-6 text-primary-600 dark:text-primary-400" />
                    </div>

                    {{-- Text --}}
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                            Thêm tài liệu pháp lý
                        </h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            Kéo thả hoặc <span class="text-primary-600 dark:text-primary-400 font-medium">click để chọn</span>
                        </p>
                    </div>

                    {{-- File Types --}}
                    <div class="hidden sm:flex items-center gap-2">
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">
                            PDF
                        </span>
                        <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                            IMG
                        </span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">≤10MB</span>
                    </div>
                </div>
            </div>
        </label>

        <input
            type="file"
            id="upload-docs-{{ $property->id }}"
            wire:model="newFiles"
            multiple
            accept="image/jpeg,image/png,image/webp,application/pdf"
            class="sr-only" />
    </div>

    {{-- Preview Selected Files --}}
    @if(count($newFiles) > 0)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ count($newFiles) }} file đã chọn</span>
            <button
                type="button"
                wire:click="$set('newFiles', [])"
                class="text-xs text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400">
                Xoá tất cả
            </button>
        </div>
        {{-- Auto-upload Status --}}
        <div class="px-3 py-2 bg-primary-50 dark:bg-primary-900/20 border-t border-gray-200 dark:border-gray-700 flex items-center justify-center gap-2">
            <div class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></div>
            <span class="text-xs font-medium text-primary-700 dark:text-primary-300">Đang lưu tài liệu...</span>
        </div>
    </div>
    @endif

    {{-- Existing Files List --}}
    @if($files->isNotEmpty())
    <div>
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
            <x-heroicon-m-folder-open class="w-4 h-4 text-gray-500" />
            Tài liệu hiện có
            <span class="text-xs font-normal text-gray-500 dark:text-gray-400">({{ $files->count() }})</span>
        </h4>

        <div class="space-y-2">
            @foreach($files as $file)
            <div class="flex items-center gap-3 p-3 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transition-colors group">
                {{-- Icon/Thumbnail --}}
                <div class="flex-shrink-0">
                    @if(str_starts_with($file->mime_type, 'image/'))
                    <div class="w-12 h-12 rounded-lg overflow-hidden ring-1 ring-gray-200 dark:ring-gray-700">
                        <a href="{{ asset('storage/' . $file->path) }}" data-fancybox="docs" data-caption="{{ $file->original_name }}">
                            <img src="{{ asset('storage/' . $file->path) }}" alt="" class="w-full h-full object-cover" />
                        </a>
                    </div>
                    @else
                    <div class="w-12 h-12 rounded-lg bg-gradient-to-br from-red-100 to-red-200 dark:from-red-900/30 dark:to-red-800/30 flex items-center justify-center">
                        <x-heroicon-o-document-text class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    @endif
                </div>

                {{-- Info --}}
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ pathinfo($file->original_name, PATHINFO_FILENAME) }}</p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $file->human_size }}</span>
                        <span class="w-1 h-1 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ strtoupper(pathinfo($file->original_name, PATHINFO_EXTENSION)) }}</span>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a
                        href="{{ asset('storage/' . $file->path) }}"
                        @if(str_starts_with($file->mime_type, 'image/')) data-fancybox="docs-preview" data-caption="{{ $file->original_name }}" @else target="_blank" @endif
                        class="p-2 rounded-lg text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 transition-colors"
                        title="Xem">
                        <x-heroicon-m-eye class="w-4 h-4" />
                    </a>

                    <button
                        type="button"
                        wire:click="openEditModal({{ $file->id }}, '{{ addslashes($file->original_name) }}')"
                        class="p-2 rounded-lg text-gray-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-colors"
                        title="Sửa tên">
                        <x-heroicon-m-pencil class="w-4 h-4" />
                    </button>

                    <button
                        type="button"
                        wire:click="deleteFile({{ $file->id }})"
                        wire:confirm="Bạn có chắc muốn xoá tài liệu này?"
                        class="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                        title="Xoá">
                        <x-heroicon-m-trash class="w-4 h-4" />
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @else
    <div class="text-center py-8 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-dashed border-gray-300 dark:border-gray-600">
        <x-heroicon-o-document class="mx-auto h-10 w-10 text-gray-400" />
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Chưa có tài liệu nào</p>
    </div>
    @endif

    {{-- Edit Modal --}}
    <x-filament::modal id="edit-file-name" width="md">
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <x-heroicon-m-pencil-square class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <span class="text-lg font-semibold">Sửa tên tài liệu</span>
            </div>
        </x-slot>

        <x-filament::input.wrapper>
            <x-filament::input
                type="text"
                wire:model="editingFileName"
                placeholder="Nhập tên mới" />
        </x-filament::input.wrapper>

        <x-slot name="footerActions">
            <x-filament::button wire:click="closeEditModal" color="gray">Huỷ</x-filament::button>
            <x-filament::button wire:click="saveFileName" icon="heroicon-m-check">Lưu</x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>