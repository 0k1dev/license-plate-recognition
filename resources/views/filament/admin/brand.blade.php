@php
$isLogin = request()->routeIs('filament.admin.auth.login');
@endphp

@if ($isLogin)
{{-- LOGIN PAGE: Centered, Spacious, Logo Only --}}
<div class="flex justify-center w-full mb-6">
    @if ($logo)
    <img
        src="{{ $logo }}"
        alt="{{ $name }}"
        class="object-contain transition-all"
        style="height: auto; width: auto; max-width: 160px;" />
    @else
    <span class="text-3xl font-bold tracking-tight text-gray-950 dark:text-white">
        {{ $name }}
    </span>
    @endif
    <style>
        .fi-logo {
            height: 6rem !important;
        }
    </style>
</div>
@else
{{-- SIDEBAR: Horizontal, Compact --}}
<div class="fi-logo flex items-center gap-x-3 transition-opacity duration-300" x-data>
    @if ($logo)
    <img
        src="{{ $logo }}"
        alt="{{ $name }}"
        class="h-9 w-auto object-contain" />
    @endif

    <span
        x-show="! $store.sidebar.isCollapsed"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-x-2"
        x-transition:enter-end="opacity-100 translate-x-0"
        class="text-lg font-bold leading-5 tracking-tight text-gray-950 dark:text-white whitespace-nowrap">
        {{ $name }}
    </span>
</div>
@endif