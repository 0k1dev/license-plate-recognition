<?php

declare(strict_types=1);
namespace App\Filament\Pages\Auth;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),

                        // Custom Fields
                        FileUpload::make('avatar_url')
                            ->label('Ảnh đại diện')
                            ->disk('public')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('avatars')
                            ->columnSpan('full'),

                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->maxLength(20)
                            ->required(),

                        DatePicker::make('dob')
                            ->label('Ngày sinh')
                            ->displayFormat('d/m/Y'),

                        FileUpload::make('cccd_image')
                            ->label('Ảnh CCCD/CMND')
                            ->disk('public')
                            ->image()
                            ->directory('cccd')
                            ->columnSpan('full'),

                        Textarea::make('permanent_address')
                            ->label('Địa chỉ thường trú')
                            ->rows(2)
                            ->columnSpan('full'),

                        Textarea::make('current_address')
                            ->label('Địa chỉ hiện tại')
                            ->rows(2)
                            ->columnSpan('full'),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
            ),
        ];
    }
}
