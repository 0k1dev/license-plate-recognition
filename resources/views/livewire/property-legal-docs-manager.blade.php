@php
use Illuminate\Support\Facades\Storage;
/** @var \App\Models\Property $property */
$newFiles = $newFiles ?? [];
$files = $files ?? collect();
$editingFileName = $editingFileName ?? '';
$isViewMode = $isViewMode ?? false;
@endphp

<div
    class="space-y-6"
    x-data="{
        isDragging: false,
        isUploading: false,
        progress: 0,
        init() {
            this.loadFancybox();
            this.loadSortable();
        },
        loadFancybox() {
            if (!document.getElementById('fancybox-css')) {
                const link = document.createElement('link');
                link.id = 'fancybox-css';
                link.rel = 'stylesheet';
                link.href = 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css';
                document.head.appendChild(link);
            }
            if (typeof Fancybox === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js';
                script.onload = () => this.initFancybox();
                document.head.appendChild(script);
            } else {
                this.initFancybox();
            }
        },
        initFancybox() {
            Fancybox.bind('[data-fancybox]', {
                zIndex: 999999,
                hash: false,
            });
        },
        loadSortable() {
             if (typeof Sortable === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js';
                script.onload = () => this.initSortable();
                document.head.appendChild(script);
            } else {
                this.initSortable();
            }
        },
        initSortable() {
            const el = this.$refs.sortableGrid;
            if (el) {
                new Sortable(el, {
                    handle: '[wire\\:sortable\\.handle]',
                    animation: 150,
                    ghostClass: 'opacity-50',
                    onEnd: (evt) => {
                        const items = Array.from(el.querySelectorAll('[wire\\:sortable\\.item]'))
                            .map(item => item.getAttribute('wire:sortable.item'));
                        this.$wire.reorderFiles(items);
                    }
                });
            }
        }
    }"
    x-init="init()"
    x-on:livewire-upload-start="isUploading = true"
    x-on:livewire-upload-finish="isUploading = false"
    x-on:livewire-upload-error="isUploading = false"
    x-on:livewire-upload-progress="progress = $event.detail.progress">

    {{-- Upload Section (chỉ hiện ở Edit mode) --}}
    @if(!$isViewMode)
    <div
        class="relative"
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="isDragging = false">

        {{-- Loading/Progress Overlay --}}
        <div x-show="isUploading" class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/90 dark:bg-gray-900/90 rounded-xl backdrop-blur-sm transition-opacity" style="display: none;">
            <div class="w-full max-w-xs px-4">
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Đang tải lên...</span>
                    <span class="text-sm font-medium text-primary-700 dark:text-primary-300" x-text="progress + '%'"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
                    <div class="bg-primary-600 h-2.5 rounded-full transition-all duration-300" :style="'width: ' + progress + '%'"></div>
                </div>
            </div>
        </div>

        {{-- Processing Overlay (Server-side) --}}
        <div wire:loading wire:target="newFiles" class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 rounded-xl backdrop-blur-sm">
            <div class="flex flex-col items-center gap-3">
                <div class="w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Đang xử lý tài liệu...</span>
            </div>
        </div>

        <label
            for="upload-docs-{{ $property->id }}"
            class="block cursor-pointer">
            <div
                class="relative overflow-hidden rounded-xl border-2 border-dashed transition-all duration-300"
                :class="isDragging 
                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-900/20 scale-[1.02]' 
                    : 'border-gray-300 dark:border-gray-600 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 hover:border-primary-400 hover:bg-primary-50/50 dark:hover:bg-primary-900/10'">

                {{-- Background Pattern --}}
                <div class="absolute inset-0 opacity-5">
                    <svg class="w-full h-full" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%239C92AC\' fill-opacity=\'0.4\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')"></svg>
                </div>

                <div class="relative px-6 py-8">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative mb-4">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-800/50 dark:to-primary-700/50 flex items-center justify-center shadow-lg shadow-primary-500/20">
                                <x-heroicon-o-document-arrow-up class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center shadow-lg">
                                <x-heroicon-m-plus class="w-4 h-4 text-white" />
                            </div>
                        </div>

                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                            Kéo thả tài liệu vào đây
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            hoặc <span class="text-primary-600 dark:text-primary-400 font-medium hover:underline">click để chọn</span>
                        </p>

                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400">
                                <x-heroicon-m-document-text class="w-3.5 h-3.5" />
                                PDF
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                                <x-heroicon-m-photo class="w-3.5 h-3.5" />
                                Ảnh (JPG, PNG)
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                                <x-heroicon-m-arrow-up-circle class="w-3.5 h-3.5" />
                                Max 50MB
                            </span>
                        </div>
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
    @endif

    {{-- Error Messages --}}
    @error('newFiles')
    <div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm flex items-center gap-2">
        <x-heroicon-m-exclamation-circle class="w-5 h-5 flex-shrink-0" />
        {{ $message }}
    </div>
    @enderror
    @error('newFiles.*')
    <div class="p-3 bg-red-50 text-red-700 rounded-lg text-sm flex items-center gap-2">
        <x-heroicon-m-exclamation-circle class="w-5 h-5 flex-shrink-0" />
        {{ $message }}
    </div>
    @enderror

    {{-- Preview Selected --}}
    @if(count($newFiles) > 0)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <x-heroicon-m-clipboard-document-list class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Tài liệu đã chọn</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($newFiles) }} file đang tải lên...</p>
                </div>
            </div>
            <button
                type="button"
                wire:click="$set('newFiles', [])"
                class="text-xs text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">
                Xoá tất cả
            </button>
        </div>
        <div class="px-4 py-3 bg-primary-50 dark:bg-primary-900/20 border-t border-gray-200 dark:border-gray-700 flex items-center justify-center gap-2">
            <div class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></div>
            <span class="text-xs font-medium text-primary-700 dark:text-primary-300">Đang xử lý, vui lòng đợi...</span>
        </div>
    </div>
    @endif

    {{-- File List --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <x-heroicon-m-folder-open class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </span>
                Tài liệu hiện có
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    {{ $files->count() }}
                </span>
            </h4>

            @if(!$isViewMode && $files->isNotEmpty())
            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                <x-heroicon-m-arrows-up-down class="w-4 h-4" />
                Kéo thả để sắp xếp
            </p>
            @endif
        </div>

        @if($files->isEmpty())
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <x-heroicon-o-document-magnifying-glass class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Chưa có tài liệu nào</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 max-w-xs mx-auto">
                Upload giấy tờ pháp lý như Sổ đỏ, Hợp đồng mua bán. Chỉ Admin và người đăng mới có thể xem.
            </p>
        </div>
        @else
        <div
            x-ref="sortableGrid"
            class="grid gap-4"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));">
            @foreach($files as $file)
            <div
                wire:sortable.item="{{ $file->id }}"
                wire:key="file-{{ $file->id }}"
                class="relative group rounded-xl overflow-hidden border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-primary-300 dark:hover:border-primary-700 hover:shadow-md transition-all duration-200">

                {{-- Drag Handle (chỉ Edit mode) --}}
                @if(!$isViewMode)
                <div wire:sortable.handle class="absolute top-2 right-2 z-10 p-1.5 bg-white/80 dark:bg-black/50 backdrop-blur-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing hover:text-primary-600">
                    <x-heroicon-m-bars-3 class="w-4 h-4" />
                </div>
                @endif

                {{-- Preview Section --}}
                <div class="aspect-[4/3] bg-gray-50 dark:bg-gray-900 flex items-center justify-center relative border-b border-gray-100 dark:border-gray-700/50 group/preview">
                    @php
                    $fileUrl = $file->visibility === 'PRIVATE'
                    ? route('property.legal-docs.show', ['file' => $file->id])
                    : asset('storage/' . $file->path);
                    @endphp

                    @if(str_starts_with($file->mime_type, 'image/'))
                    <a href="{{ $fileUrl }}"
                        data-fancybox="docs-gallery"
                        data-caption="{{ $file->original_name }}"
                        class="w-full h-full">
                        <img
                            src="{{ $fileUrl }}"
                            alt="{{ $file->original_name }}"
                            class="w-full h-full object-cover" />
                    </a>
                    @else
                    {{-- PDF/Doc Preview --}}
                    <div class="flex flex-col items-center justify-center p-4 text-center">
                        <x-heroicon-o-document-text class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-2" />
                        <span class="text-xs font-bold text-gray-400 uppercase tracking-wider">{{ pathinfo($file->original_name, PATHINFO_EXTENSION) }}</span>
                    </div>
                    <a href="{{ $fileUrl }}" target="_blank" class="absolute inset-0 z-0"></a>
                    @endif

                    {{-- Privacy Badge --}}
                    @if(!$isViewMode)
                    <button
                        type="button"
                        wire:click="toggleVisibility({{ $file->id }})"
                        class="absolute top-2 left-2 z-10 transition-transform hover:scale-105"
                        title="Click để đổi trạng thái">
                        @if($file->visibility === 'PRIVATE')
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/60 dark:text-orange-300 shadow-sm border border-orange-200 dark:border-orange-800">
                            <x-heroicon-m-lock-closed class="w-3 h-3" />
                            Riêng tư
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/60 dark:text-green-300 shadow-sm border border-green-200 dark:border-green-800">
                            <x-heroicon-m-globe-alt class="w-3 h-3" />
                            Công khai
                        </span>
                        @endif
                    </button>
                    @else
                    {{-- View mode: chỉ hiện badge, không click được --}}
                    <div class="absolute top-2 left-2 z-10">
                        @if($file->visibility === 'PRIVATE')
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 dark:bg-orange-900/60 dark:text-orange-300 shadow-sm border border-orange-200 dark:border-orange-800">
                            <x-heroicon-m-lock-closed class="w-3 h-3" />
                            Riêng tư
                        </span>
                        @else
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-green-100 text-green-700 dark:bg-green-900/60 dark:text-green-300 shadow-sm border border-green-200 dark:border-green-800">
                            <x-heroicon-m-globe-alt class="w-3 h-3" />
                            Công khai
                        </span>
                        @endif
                    </div>
                    @endif
                </div>

                {{-- Info Section --}}
                <div class="p-3">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h5 class="text-sm font-medium text-gray-900 dark:text-white truncate flex-1" title="{{ $file->original_name }}">
                            {{ pathinfo($file->original_name, PATHINFO_FILENAME) }}
                        </h5>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1 mb-3">
                        <span class="truncate">{{ $file->human_size }}</span>
                        <span class="text-gray-300 dark:text-gray-600">•</span>
                        <span class="truncate">{{ $file->created_at->format('d/m/Y') }}</span>
                    </p>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1">
                        <a
                            href="{{ $file->visibility === 'PRIVATE' ? route('property.legal-docs.show', ['file' => $file->id]) : asset('storage/' . $file->path) }}"
                            target="_blank"
                            class="flex-1 inline-flex items-center justify-center py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-blue-50 hover:text-blue-600 dark:bg-gray-700/50 dark:text-gray-400 dark:hover:bg-blue-900/30 dark:hover:text-blue-400 transition-colors"
                            title="Tải về">
                            <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                        </a>

                        @if(!$isViewMode)
                        <button
                            type="button"
                            wire:click="openEditModal({{ $file->id }}, '{{ addslashes($file->original_name) }}')"
                            class="flex-1 inline-flex items-center justify-center py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-primary-50 hover:text-primary-600 dark:bg-gray-700/50 dark:text-gray-400 dark:hover:bg-primary-900/30 dark:hover:text-primary-400 transition-colors"
                            title="Đổi tên">
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>

                        <button
                            type="button"
                            wire:click="deleteFile({{ $file->id }})"
                            wire:confirm="Bạn có chắc muốn xoá tài liệu này?"
                            class="flex-1 inline-flex items-center justify-center py-1.5 rounded-lg bg-gray-50 text-gray-600 hover:bg-red-50 hover:text-red-600 dark:bg-gray-700/50 dark:text-gray-400 dark:hover:bg-red-900/30 dark:hover:text-red-400 transition-colors"
                            title="Xoá">
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

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