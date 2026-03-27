<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Diagnostics Section --}}
        <x-filament::section collapsible collapsed title="Kiểm tra hệ thống (Diagnostics)">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach($diagnostics as $label => $value)
                    <div class="flex items-center justify-between p-3 rounded-lg border border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 shadow-sm">
                        <span class="text-sm font-medium text-gray-500">{{ $label }}</span>
                        @if(is_bool($value))
                            @if($value)
                                <x-filament::badge color="success" icon="heroicon-m-check-circle">OK</x-filament::badge>
                            @else
                                <x-filament::badge color="danger" icon="heroicon-m-x-circle">Lỗi</x-filament::badge>
                            @endif
                        @else
                            <span class="text-sm font-bold text-primary-600">{{ $value }}</span>
                        @endif
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-gray-500 italic">* Lưu ý: Nếu có mục "Lỗi" màu đỏ, vui lòng yêu cầu hosting hỗ trợ bật extension hoặc cấp quyền ghi file.</p>
        </x-filament::section>

        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <x-filament::input.wrapper class="w-40">
                    <x-filament::input
                        type="number"
                        wire:model.live="linesLimit"
                        placeholder="Số dòng"
                    />
                </x-filament::input.wrapper>
                <span class="text-sm text-gray-500">dòng cuối</span>
            </div>

            <div class="flex items-center gap-2">
                <x-filament::button
                    color="gray"
                    icon="heroicon-m-arrow-path"
                    wire:click="refreshLogs"
                >
                    Làm mới
                </x-filament::button>

                <x-filament::button
                    color="warning"
                    icon="heroicon-m-arrow-down-tray"
                    wire:click="downloadLogs"
                >
                    Tải về (.log)
                </x-filament::button>

                <x-filament::button
                    color="danger"
                    icon="heroicon-m-trash"
                    wire:confirm="Bạn có chắc chắn muốn xóa toàn bộ log?"
                    wire:click="clearLogs"
                >
                    Xóa log
                </x-filament::button>
            </div>
        </div>

        <x-filament::section>
            <div 
                class="p-4 bg-gray-900 rounded-lg overflow-x-auto font-mono text-sm text-gray-300 max-h-[600px] overflow-y-auto border border-gray-700"
                id="log-container"
            >
                <pre class="whitespace-pre-wrap"><code>{{ $logContent }}</code></pre>
            </div>
        </x-filament::section>
    </div>

    @script
    <script>
        const container = document.getElementById('log-container');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
        
        $wire.on('refreshLogs', () => {
            setTimeout(() => {
                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        });
    </script>
    @endscript
</x-filament-panes::page>
