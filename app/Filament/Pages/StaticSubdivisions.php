<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class StaticSubdivisions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationLabel = 'Tra cứu Phường/Xã';
    protected static ?string $title = 'Tra cứu Phường/Xã (Tốc độ cao)';
    protected static ?string $navigationGroup = 'Danh mục';
    protected static string $view = 'filament.pages.static-subdivisions';
    protected static ?int $navigationSort = 2;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Cập nhật lại dữ liệu')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    Artisan::call('app:export-areas');
                    Notification::make()
                        ->title('Đã cập nhật file dữ liệu JSON thành công!')
                        ->success()
                        ->send();

                    return redirect(request()->header('Referer'));
                })
        ];
    }
}
