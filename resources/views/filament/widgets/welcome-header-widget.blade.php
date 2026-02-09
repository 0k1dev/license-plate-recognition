<x-filament-widgets::widget class="fi-welcome-header-widget">
    <div class="relative overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="absolute top-0 right-0 -mt-20 -mr-20 h-80 w-80 rounded-full bg-gradient-to-tr from-indigo-400/20 to-teal-400/20 blur-3xl"></div>

        <div class="relative flex flex-col gap-6 p-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <div class="relative">
                    <x-filament::avatar
                        :src="$user->getFilamentAvatarUrl()"
                        alt="{{ $user->name }}"
                        size="lg"
                        class="h-16 w-16 ring-2 ring-white dark:ring-gray-800 shadow-md" />
                    <div class="absolute bottom-0 right-0 h-4 w-4 rounded-full bg-green-500 ring-2 ring-white dark:ring-gray-800"></div>
                </div>

                <div>
                    <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">
                        {{ $greeting }}, {{ $user->name }}! 👋
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        "{{ $quote }}"
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <x-filament::button
                    tag="a"
                    href="{{ \App\Filament\Resources\PostResource::getUrl('create') }}"
                    icon="heroicon-m-pencil-square"
                    color="primary"
                    size="md">
                    Đăng tin mới
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="/admin/owner-phone-requests"
                    icon="heroicon-m-phone"
                    color="success"
                    outlined
                    size="md">
                    Yêu cầu SĐT
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    href="/admin/properties"
                    icon="heroicon-m-home"
                    color="gray"
                    outlined
                    size="md">
                    BĐS của tôi
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>