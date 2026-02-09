<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class StaticProvinces extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Tra cứu Tỉnh/Thành';
    protected static ?string $title = 'Tra cứu Tỉnh/Thành phố';
    protected static ?string $navigationGroup = 'Danh mục';
    protected static string $view = 'filament.pages.static-provinces';
    protected static ?int $navigationSort = 1;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Cập nhật dữ liệu')
                ->icon('heroicon-m-arrow-path')
                ->color('gray')
                ->action(function () {
                    Artisan::call('app:export-areas');
                    Notification::make()
                        ->title('Đã cập nhật file dữ liệu JSON!')
                        ->success()
                        ->send();

                    return redirect(request()->header('Referer'));
                })
        ];
    }
}
