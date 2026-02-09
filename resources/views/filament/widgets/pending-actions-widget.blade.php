<x-filament-widgets::widget>
    @php
    $data = $this->getData();
    $propertyCount = $data['property_count'] ?? 0;
    $requestCount = $data['request_count'] ?? 0;
    $reportCount = $data['report_count'] ?? 0;
    $hasPending = $propertyCount > 0 || $requestCount > 0 || $reportCount > 0;
    $totalCount = $propertyCount + $requestCount + $reportCount;
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-lg shadow-md">
                    <x-heroicon-m-bolt class="h-5 w-5 text-white" />
                </div>
                <div>
                    <span class="font-bold text-lg text-gray-800 dark:text-gray-100">Cần Xử Lý</span>
                    <p class="text-xs text-gray-500 font-normal">Các tác vụ cần sự chú ý của bạn</p>
                </div>
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            @if($hasPending)
            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 border border-orange-200 dark:border-orange-800/30">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-orange-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-orange-500"></span>
                </span>
                <span class="text-sm font-bold">{{ $totalCount }} việc mới</span>
            </div>
            @else
            <div class="flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800/30">
                <x-heroicon-m-check-circle class="w-4 h-4" />
                <span class="text-sm font-bold">Hoàn thành</span>
            </div>
            @endif
        </x-slot>

        @if($hasPending)
        <div class="grid gap-6 md:grid-cols-3">
            {{-- BĐS Pending --}}
            <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 hover:shadow-md transition-all duration-300 dark:bg-gray-900 dark:ring-white/10">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <x-heroicon-o-home-modern class="w-24 h-24 text-amber-500 rotate-12" />
                </div>

                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-amber-100 dark:bg-amber-900/40 rounded-md">
                                <x-heroicon-m-home class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Duyệt BĐS</h3>
                        </div>
                        @if($propertyCount > 0)
                        <span class="flex items-center justify-center h-6 w-6 rounded-full bg-amber-500 text-xs font-bold text-white shadow-sm ring-2 ring-white dark:ring-gray-900">
                            {{ $propertyCount }}
                        </span>
                        @endif
                    </div>

                    <div class="space-y-1 mb-4">
                        @forelse($data['pending_properties'] as $prop)
                        <div class="group/item flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-800 last:border-0">
                            <div class="flex-1 min-w-0 pr-3">
                                <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-200 group-hover/item:text-amber-600 transition-colors">
                                    {{ $prop->title }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                    <x-heroicon-m-map-pin class="w-3 h-3" />
                                    {{ $prop->areaLocation?->name }} • {{ $prop->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <a href="{{ route('filament.admin.resources.properties.edit', $prop) }}"
                                class="text-xs font-semibold text-amber-600 hover:text-amber-700 bg-amber-50 hover:bg-amber-100 px-2.5 py-1.5 rounded transition-colors dark:bg-amber-900/30 dark:text-amber-400">
                                Xem
                            </a>
                        </div>
                        @empty
                        <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu</div>
                        @endforelse
                    </div>

                    <a href="{{ route('filament.admin.resources.properties.index', ['tableFilters[approval_status][value]' => 'PENDING']) }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:bg-gray-800/50 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white transition-colors">
                        Xem tất cả
                        <x-heroicon-m-arrow-right class="h-4 w-4" />
                    </a>
                </div>
            </div>

            {{-- Yêu cầu SĐT --}}
            <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 hover:shadow-md transition-all duration-300 dark:bg-gray-900 dark:ring-white/10">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <x-heroicon-o-phone class="w-24 h-24 text-indigo-500 -rotate-12" />
                </div>

                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-indigo-100 dark:bg-indigo-900/40 rounded-md">
                                <x-heroicon-m-phone class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Yêu cầu SĐT</h3>
                        </div>
                        @if($requestCount > 0)
                        <span class="flex items-center justify-center h-6 w-6 rounded-full bg-indigo-500 text-xs font-bold text-white shadow-sm ring-2 ring-white dark:ring-gray-900">
                            {{ $requestCount }}
                        </span>
                        @endif
                    </div>

                    <div class="space-y-1 mb-4">
                        @forelse($data['pending_requests'] as $req)
                        <div class="group/item flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-800 last:border-0">
                            <div class="flex-1 min-w-0 pr-3">
                                <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-200 group-hover/item:text-indigo-600 transition-colors">
                                    {{ $req->requester?->name }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $req->property?->title }}
                                </p>
                            </div>
                            <a href="{{ route('filament.admin.resources.owner-phone-requests.index') }}"
                                class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1.5 rounded transition-colors dark:bg-indigo-900/30 dark:text-indigo-400">
                                Duyệt
                            </a>
                        </div>
                        @empty
                        <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu</div>
                        @endforelse
                    </div>

                    <a href="{{ route('filament.admin.resources.owner-phone-requests.index', ['tableFilters[status][value]' => 'PENDING']) }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:bg-gray-800/50 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white transition-colors">
                        Xem tất cả
                        <x-heroicon-m-arrow-right class="h-4 w-4" />
                    </a>
                </div>
            </div>

            {{-- Báo cáo --}}
            <div class="group relative overflow-hidden rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 hover:shadow-md transition-all duration-300 dark:bg-gray-900 dark:ring-white/10">
                <div class="absolute top-0 right-0 p-3 opacity-10 group-hover:opacity-20 transition-opacity">
                    <x-heroicon-o-flag class="w-24 h-24 text-rose-500 rotate-6" />
                </div>

                <div class="relative">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 bg-rose-100 dark:bg-rose-900/40 rounded-md">
                                <x-heroicon-m-exclamation-triangle class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                            </div>
                            <h3 class="font-bold text-gray-900 dark:text-white">Báo cáo</h3>
                        </div>
                        @if($reportCount > 0)
                        <span class="flex items-center justify-center h-6 w-6 rounded-full bg-rose-500 text-xs font-bold text-white shadow-sm ring-2 ring-white dark:ring-gray-900">
                            {{ $reportCount }}
                        </span>
                        @endif
                    </div>

                    <div class="space-y-1 mb-4">
                        @forelse($data['pending_reports'] as $report)
                        <div class="group/item flex items-center justify-between py-3 border-b border-gray-100 dark:border-gray-800 last:border-0">
                            <div class="flex-1 min-w-0 pr-3">
                                <p class="truncate text-sm font-medium text-gray-700 dark:text-gray-200 group-hover/item:text-rose-600 transition-colors">
                                    {{ Str::limit($report->content, 30) }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $report->type }} • {{ $report->created_at->diffForHumans() }}
                                </p>
                            </div>
                            <a href="{{ route('filament.admin.resources.reports.index') }}"
                                class="text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1.5 rounded transition-colors dark:bg-rose-900/30 dark:text-rose-400">
                                Xử lý
                            </a>
                        </div>
                        @empty
                        <div class="text-center py-6 text-gray-400 text-sm">Không có dữ liệu</div>
                        @endforelse
                    </div>

                    <a href="{{ route('filament.admin.resources.reports.index', ['tableFilters[status][value]' => 'NEW']) }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-gray-50 px-3 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:bg-gray-800/50 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white transition-colors">
                        Xem tất cả
                        <x-heroicon-m-arrow-right class="h-4 w-4" />
                    </a>
                </div>
            </div>
        </div>
        @else
        <div class="flex flex-col items-center justify-center py-12 text-center bg-gray-50/50 dark:bg-gray-900/50 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
            <div class="p-4 bg-gradient-to-br from-emerald-100 to-teal-100 dark:from-emerald-900/30 dark:to-teal-900/30 rounded-full mb-4 shadow-sm animate-pulse">
                <x-heroicon-o-check class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
            </div>
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                Mọi thứ đều ổn!
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2 max-w-xs mx-auto">
                Không có công việc nào cần xử lý ngay bây giờ. Hãy tận hưởng tách cà phê! ☕
            </p>
        </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>