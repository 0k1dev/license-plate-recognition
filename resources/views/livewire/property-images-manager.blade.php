@php
use Illuminate\Support\Facades\Storage;
/** @var \App\Models\Property $property */
$newImages = $newImages ?? [];
$images = $images ?? collect();
$editingImageName = $editingImageName ?? '';
@endphp

<div
    class="space-y-6"
    x-data="{
        isDragging: false,
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
                        this.$wire.reorderImages(items);
                    }
                });
            }
        }
    }"
    x-init="init()">
    {{-- Upload Section - Dropzone Style --}}
    <div
        class="relative"
        x-on:dragover.prevent="isDragging = true"
        x-on:dragleave.prevent="isDragging = false"
        x-on:drop.prevent="isDragging = false">
        {{-- Loading Overlay --}}
        <div wire:loading wire:target="newImages" class="absolute inset-0 z-20 flex items-center justify-center bg-white/80 dark:bg-gray-900/80 rounded-xl backdrop-blur-sm">
            <div class="flex flex-col items-center gap-3">
                <div class="w-10 h-10 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Đang xử lý ảnh...</span>
            </div>
        </div>

        <label
            for="upload-images-{{ $property->id }}"
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
                        {{-- Icon --}}
                        <div class="relative mb-4">
                            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-100 to-primary-200 dark:from-primary-800/50 dark:to-primary-700/50 flex items-center justify-center shadow-lg shadow-primary-500/20">
                                <x-heroicon-o-cloud-arrow-up class="w-8 h-8 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-6 h-6 rounded-full bg-green-500 flex items-center justify-center shadow-lg">
                                <x-heroicon-m-plus class="w-4 h-4 text-white" />
                            </div>
                        </div>

                        {{-- Text --}}
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                            Kéo thả ảnh vào đây
                        </h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">
                            hoặc <span class="text-primary-600 dark:text-primary-400 font-medium hover:underline">click để chọn ảnh</span>
                        </p>

                        {{-- File Info Tags --}}
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400">
                                <x-heroicon-m-photo class="w-3.5 h-3.5" />
                                JPG, PNG, WebP, GIF
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400">
                                <x-heroicon-m-arrow-up-circle class="w-3.5 h-3.5" />
                                Tối đa 5MB/ảnh
                            </span>
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400">
                                <x-heroicon-m-squares-plus class="w-3.5 h-3.5" />
                                Chọn nhiều ảnh
                            </span>
                        </div>

                        {{-- Note --}}
                        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                            <x-heroicon-m-information-circle class="w-4 h-4" />
                            Ảnh mới sẽ được thêm vào, không xoá ảnh cũ
                        </p>
                    </div>
                </div>
            </div>
        </label>

        {{-- Hidden File Input --}}
        <input
            type="file"
            id="upload-images-{{ $property->id }}"
            wire:model="newImages"
            multiple
            accept="image/jpeg,image/png,image/webp,image/gif"
            class="sr-only" />
    </div>

    {{-- Preview Selected Images --}}
    @if(count($newImages) > 0)
    <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden shadow-sm">
        {{-- Header --}}
        <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <x-heroicon-m-photo class="w-4 h-4 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">Ảnh đã chọn</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ count($newImages) }} ảnh sẵn sàng tải lên</p>
                </div>
            </div>
            <button
                type="button"
                wire:click="$set('newImages', [])"
                class="text-xs text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400 transition-colors">
                Xoá tất cả
            </button>
        </div>

        {{-- Preview Grid --}}
        <div class="p-4">
            <div class="grid gap-3" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));">
                @foreach($newImages as $index => $image)
                <div class="relative group aspect-square rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 ring-1 ring-gray-200 dark:ring-gray-600">
                    <img
                        src="{{ $image->temporaryUrl() }}"
                        class="w-full h-full object-cover"
                        alt="Preview" />
                    {{-- Overlay with number --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                        <span class="absolute bottom-1.5 left-1.5 text-xs font-medium text-white bg-black/50 px-1.5 py-0.5 rounded">
                            #{{ $index + 1 }}
                        </span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Auto-upload Status --}}
        <div class="px-4 py-3 bg-primary-50 dark:bg-primary-900/20 border-t border-gray-200 dark:border-gray-700 flex items-center justify-center gap-2">
            <div class="w-2 h-2 rounded-full bg-primary-500 animate-pulse"></div>
            <span class="text-xs font-medium text-primary-700 dark:text-primary-300">Đang lưu ảnh vào hệ thống...</span>
        </div>
    </div>
    @endif

    {{-- Existing Images Gallery --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                    <x-heroicon-m-photo class="w-4 h-4 text-gray-600 dark:text-gray-400" />
                </span>
                Ảnh hiện có
                <span class="inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                    {{ $images->count() }}
                </span>
            </h4>
            @if($images->isNotEmpty())
            <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                <x-heroicon-m-arrows-up-down class="w-4 h-4" />
                Kéo thả để sắp xếp
            </p>
            @endif
        </div>

        @if($images->isEmpty())
        <div class="text-center py-12 bg-gray-50 dark:bg-gray-800/50 rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                <x-heroicon-o-photo class="w-8 h-8 text-gray-400" />
            </div>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Chưa có ảnh nào</p>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Thêm ảnh bằng cách kéo thả hoặc click vào vùng upload ở trên</p>
        </div>
        @else
        <div
            x-ref="sortableGrid"
            class="grid gap-4"
            style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));">
            @foreach($images as $image)
            <div
                wire:sortable.item="{{ $image->id }}"
                wire:key="image-{{ $image->id }}"
                class="relative group rounded-xl overflow-hidden border-2 transition-all duration-200 {{ $image->is_primary ? 'border-primary-500 ring-2 ring-primary-200 dark:ring-primary-800 shadow-lg shadow-primary-500/10' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }} bg-white dark:bg-gray-800">
                {{-- Drag Handle --}}
                <div wire:sortable.handle class="absolute top-2 right-2 z-10 p-1.5 bg-black/60 backdrop-blur-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity cursor-grab active:cursor-grabbing">
                    <x-heroicon-m-bars-3 class="w-4 h-4 text-white" />
                </div>

                {{-- Primary Badge --}}
                @if($image->is_primary)
                <div class="absolute top-2 left-2 z-10">
                    <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-lg bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/30">
                        <x-heroicon-m-star class="w-3 h-3" />
                        Ảnh chính
                    </span>
                </div>
                @endif

                {{-- Image --}}
                <div class="aspect-square overflow-hidden bg-gray-50 dark:bg-gray-700 relative">
                    <a data-src="{{ asset('storage/' . $image->path) }}"
                        data-fancybox="gallery-{{ $this->getId() }}"
                        data-caption="{{ $image->original_name }}"
                        class="block w-full h-full cursor-zoom-in">
                        <img
                            src="{{ asset('storage/' . $image->path) }}"
                            alt="{{ $image->original_name }}"
                            class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            title="Click để phóng to" />
                    </a>
                </div>

                {{-- Info & Actions --}}
                <div class="p-3 space-y-2 border-t border-gray-100 dark:border-gray-700/50">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate" title="{{ $image->original_name }}">
                        {{ pathinfo($image->original_name, PATHINFO_FILENAME) }}
                    </p>

                    <div class="flex items-center gap-1.5">
                        @if(!$image->is_primary)
                        <button
                            type="button"
                            wire:click="setPrimary({{ $image->id }})"
                            class="flex-1 inline-flex items-center justify-center gap-1 px-2 py-1.5 text-xs font-medium rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-400 dark:hover:bg-amber-900/50 transition-colors"
                            title="Đặt làm ảnh chính">
                            <x-heroicon-m-star class="w-3.5 h-3.5" />
                            Chính
                        </button>
                        @else
                        <div class="flex-1"></div>
                        @endif

                        <button
                            type="button"
                            wire:click="openEditModal({{ $image->id }}, '{{ addslashes($image->original_name) }}')"
                            class="inline-flex items-center justify-center p-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-blue-100 hover:text-blue-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-blue-900/50 dark:hover:text-blue-400 transition-colors"
                            title="Sửa tên">
                            <x-heroicon-m-pencil-square class="w-4 h-4" />
                        </button>

                        <button
                            type="button"
                            wire:click="deleteImage({{ $image->id }})"
                            wire:confirm="Bạn có chắc muốn xoá ảnh này?"
                            class="inline-flex items-center justify-center p-1.5 rounded-lg bg-gray-100 text-gray-600 hover:bg-red-100 hover:text-red-600 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-red-900/50 dark:hover:text-red-400 transition-colors"
                            title="Xoá ảnh">
                            <x-heroicon-m-trash class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Edit Name Modal --}}
    <x-filament::modal id="edit-image-name" width="md">
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center">
                    <x-heroicon-m-pencil-square class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Sửa tên ảnh</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Nhập tên mới cho ảnh</p>
                </div>
            </div>
        </x-slot>

        <div class="space-y-4">
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model="editingImageName"
                    placeholder="Nhập tên ảnh mới" />
            </x-filament::input.wrapper>
        </div>

        <x-slot name="footerActions">
            <x-filament::button wire:click="closeEditModal" color="gray">
                Huỷ
            </x-filament::button>
            <x-filament::button wire:click="saveImageName" icon="heroicon-m-check">
                Lưu thay đổi
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>