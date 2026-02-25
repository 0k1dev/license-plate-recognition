<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Services\ImageService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class MyProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';
    protected static ?string $navigationLabel = 'Hồ sơ cá nhân';
    protected static ?string $slug = 'my-profile';
    protected static ?string $title = 'Hồ sơ của tôi';
    protected static bool $shouldRegisterNavigation = false; // Hide from sidebar, access via user menu

    protected static string $view = 'filament.pages.my-profile';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->attributesToArray());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Thông tin cá nhân')
                    ->description('Cập nhật thông tin cá nhân và liên hệ của bạn.')
                    ->aside()
                    ->schema([
                        FileUpload::make('avatar_url')
                            ->label('Ảnh đại diện')
                            ->disk('public')
                            ->image()
                            ->avatar()
                            ->imageEditor()
                            ->circleCropper()
                            ->directory('avatars')
                            ->columnSpanFull()
                            ->alignCenter(),

                        TextInput::make('name')
                            ->label('Họ và tên')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true),

                        TextInput::make('phone')
                            ->label('Số điện thoại')
                            ->tel()
                            ->maxLength(20)
                            ->required(),

                        DatePicker::make('dob')
                            ->label('Ngày sinh')
                            ->displayFormat('d/m/Y'),
                    ])->columns(2),

                Section::make('Địa chỉ & Giấy tờ')
                    ->aside()
                    ->schema([
                        FileUpload::make('cccd_image')
                            ->label('Ảnh CCCD/CMND')
                            ->disk('public')
                            ->image()
                            ->directory('cccd')
                            ->columnSpanFull(),

                        Textarea::make('permanent_address')
                            ->label('Địa chỉ thường trú')
                            ->rows(2)
                            ->columnSpanFull(),

                        Textarea::make('current_address')
                            ->label('Địa chỉ hiện tại')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Đổi mật khẩu')
                    ->description('Để trống nếu không muốn đổi mật khẩu.')
                    ->aside()
                    ->schema([
                        TextInput::make('new_password')
                            ->label('Mật khẩu mới')
                            ->password()
                            ->rule(Password::default()),

                        TextInput::make('new_password_confirmation')
                            ->label('Xác nhận mật khẩu')
                            ->password()
                            ->same('new_password')
                            ->requiredWith('new_password'),
                    ]),
            ])
            ->statePath('data')
            ->model(auth()->user());
    }

    public function submit(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        // Nếu avatar thay đổi → xoá thumb cũ
        if (
            !empty($user->avatar_url)
            && isset($data['avatar_url'])
            && $data['avatar_url'] !== $user->avatar_url
        ) {
            app(ImageService::class)->deleteThumbnails($user->avatar_url);
        }

        // Handle Password Update explicitly
        if (!empty($data['new_password'])) {
            $data['password'] = Hash::make($data['new_password']);
        }
        unset($data['new_password'], $data['new_password_confirmation']);

        $user->update($data);

        // Sinh thumbnail avatar mới (120×120) sau khi lưu
        if (!empty($data['avatar_url'])) {
            app(ImageService::class)->makeThumbnail($data['avatar_url'], 'avatar');
        }

        Notification::make()
            ->title('Đã cập nhật hồ sơ thành công')
            ->success()
            ->send();
    }
}
