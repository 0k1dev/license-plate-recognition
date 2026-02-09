<?php

declare(strict_types=1);
namespace App\Filament\Pages;

use Filament\Pages\Page;

class Settings extends \Filament\Pages\SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Cài đặt chung';
    protected static ?string $title = 'Cài đặt hệ thống';
    protected static ?string $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 6;

    protected static string $settings = \App\Settings\GeneralSettings::class;

    public function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                \Filament\Forms\Components\Section::make('Thông tin chung')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('site_name')
                            ->label('Tên trang web')
                            ->required(),
                    ]),
                \Filament\Forms\Components\Section::make('Giao diện đăng nhập')
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('site_logo')
                            ->label('Logo trang web')
                            ->image()
                            ->directory('settings')
                            ->visibility('public'),
                        \Filament\Forms\Components\FileUpload::make('auth_bg_image')
                            ->label('Hình nền trang đăng nhập')
                            ->image()
                            ->directory('settings')
                            ->visibility('public')
                            ->helperText('Hình ảnh hiển thị bên trái trang đăng nhập (ưu tiên ảnh dọc).'),
                    ]),
            ]);
    }
}
