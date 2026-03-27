<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Filament\Notifications\Notification;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SystemLogs extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-magnifying-glass';
    protected static ?string $navigationLabel = 'Logs Hệ thống';
    protected static ?string $navigationGroup = 'Cài đặt hệ thống';
    protected static ?int $navigationSort = 100;
    protected static string $view = 'filament.pages.system-logs';
    protected static ?string $title = 'Logs Hệ thống';

    public string $logContent = '';
    public int $linesLimit = 200;
    public array $diagnostics = [];

    public function mount(): void
    {
        // Only Super Admin can view logs
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403);
        }

        $this->refreshLogs();
        $this->checkEnv();
    }

    public function checkEnv(): void
    {
        $this->diagnostics = [
            'PHP Version' => PHP_VERSION,
            'BCMath' => extension_loaded('bcmath'),
            'GD' => extension_loaded('gd'),
            'Intl' => extension_loaded('intl'),
            'Imagick' => extension_loaded('imagick'),
            'Mbstring' => extension_loaded('mbstring'),
            'Fileinfo' => extension_loaded('fileinfo'),
            'Storage Writable' => is_writable(storage_path()),
            'Logs Writable' => is_writable(storage_path('logs')),
            'Cache Writable' => is_writable(base_path('bootstrap/cache')),
            'Public Storage Link' => File::exists(public_path('storage')),
            'Database Connected' => $this->checkDatabase(),
        ];
    }

    private function checkDatabase(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function refreshLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            $this->logContent = "Log file không tồn tại tại: {$logPath}";
            return;
        }

        $fileSize = File::size($logPath);
        if ($fileSize > 10 * 1024 * 1024) { // > 10MB
             $this->logContent = "File log quá lớn ({$fileSize} bytes). Vui lòng tải về để xem chi tiết.";
             return;
        }

        $content = File::get($logPath);
        $lines = explode("\n", $content);
        $this->logContent = implode("\n", array_slice($lines, -$this->linesLimit));
        
        Notification::make()
            ->title('Đã làm mới log')
            ->success()
            ->send();
    }

    public function downloadLogs(): BinaryFileResponse
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            Notification::make()
                ->title('Lỗi')
                ->body('File log không tồn tại.')
                ->danger()
                ->send();
            abort(404);
        }

        return response()->download($logPath);
    }

    public function clearLogs(): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        if (File::exists($logPath)) {
            File::put($logPath, '');
            $this->logContent = 'Log đã được xóa.';
            
            Notification::make()
                ->title('Thành công')
                ->body('Đã xóa toàn bộ nội dung file log.')
                ->success()
                ->send();
        }
    }
}
