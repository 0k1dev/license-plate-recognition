<?php

declare(strict_types=1);
namespace App\Filament\Pages;

use App\Settings\MailSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\SettingsPage;

class ManageMailSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $title = 'Mail Configuration';

    protected static string $settings = MailSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('SMTP Configuration')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('mail_host')
                            ->label('Mail Host')
                            ->required()
                            ->maxLength(191),
                        Forms\Components\TextInput::make('mail_port')
                            ->label('Mail Port')
                            ->numeric()
                            ->required(),
                        Forms\Components\Select::make('mail_encryption')
                            ->label('Encryption')
                            ->options([
                                'tls' => 'TLS',
                                'ssl' => 'SSL',
                                'null' => 'None',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('mail_username')
                            ->label('Username')
                            ->maxLength(191),
                        Forms\Components\TextInput::make('mail_password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->maxLength(191),
                    ]),

                Forms\Components\Section::make('Sender Identity')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('mail_from_address')
                            ->label('From Address')
                            ->email()
                            ->required(),
                        Forms\Components\TextInput::make('mail_from_name')
                            ->label('From Name')
                            ->required(),
                        Forms\Components\Hidden::make('mail_mailer')->default('smtp'),
                    ]),
            ]);
    }
}
