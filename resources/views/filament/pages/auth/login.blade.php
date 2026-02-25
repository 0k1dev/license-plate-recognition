<div class="flex w-full min-h-screen bg-white dark:bg-gray-950">
    <style>
        @media (min-width: 1024px) {
            .lg-w-75 {
                width: 70% !important;
            }

            .lg-w-25 {
                width: 30% !important;
            }
        }
    </style>
    {{-- Left Side: Image --}}
    <div class="hidden lg:flex lg-w-75 bg-cover bg-center relative bg-gray-900"
        @if($bg=$this->getBg()) style="background-image: url('{{ $bg }}');" @endif>
        <div class="absolute inset-0 bg-black/40"></div>
        {{--
        <!-- <div class="relative z-10 flex flex-col justify-end h-full p-12 text-white">
            <h1 class="text-4xl font-bold mb-4 font-heading">{{ $this->getSiteName() }}</h1>
        <p class="text-lg opacity-90 max-w-md">Cùng xây dựng hệ thống quản lý bất động sản chuyên nghiệp, hiệu quả và minh bạch.</p>
    </div> -->
    --}}
</div>

{{-- Right Side: Login Form --}}
<div class="flex w-full lg-w-25 flex-col items-center justify-center px-6 py-12 relative">
    <div class="w-full max-w-sm space-y-6">
        {{-- Logo & Site Name --}}
        <div class="flex flex-col items-center gap-y-4 mb-6">
            @if($this->getLogo())
            <img src="{{ $this->getLogo() }}" alt="{{ $this->getSiteName() }}" class="w-auto object-contain" style="max-width: 110px" />
            @else
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $this->getSiteName() }}</h2>
            @endif
            <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                {{ __('filament-panels::pages/auth/login.title') }}
            </h2>
        </div>

        {{-- Form Component --}}
        <x-filament-panels::form wire:submit="authenticate">
            {{ $this->form }}
            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()" />
        </x-filament-panels::form>

        {{-- Registration Link --}}
        @if (filament()->hasRegistration())
        <p class="text-center text-sm text-gray-600 dark:text-gray-400 mt-4">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}
            <a href="{{ filament()->getRegistrationUrl() }}" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400">
                {{ __('filament-panels::pages/auth/login.actions.register.label') }}
            </a>
        </p>
        @endif
    </div>

    {{-- Notifications --}}
    @livewire(\Filament\Livewire\Notifications::class)
</div>
</div>