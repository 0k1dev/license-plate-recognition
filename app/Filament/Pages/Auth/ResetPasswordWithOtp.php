<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Services\PasswordResetOtpService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\PasswordResetResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Validation\ValidationException;

class ResetPasswordWithOtp extends BaseResetPassword
{
    public string $otp = '';
    public ?string $password = '';
    public ?string $password_confirmation = '';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('otp')
                    ->label('Mã OTP (6 số)')
                    ->required()
                    ->length(6)
                    ->numeric()
                    ->placeholder('Nhập mã OTP từ email')
                    ->autofocus(),

                TextInput::make('password')
                    ->label('Mật khẩu mới')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->confirmed(),

                TextInput::make('password_confirmation')
                    ->label('Xác nhận mật khẩu')
                    ->password()
                    ->revealable()
                    ->required(),
            ]);
    }

    public function resetPassword(): ?PasswordResetResponse
    {
        $data = $this->form->getState();
        $email = session('otp_reset_email');

        if (!$email) {
            Notification::make()
                ->title('Phiên làm việc hết hạn. Vui lòng thực hiện lại từ bước nhập email.')
                ->danger()
                ->send();
            $this->redirect(route('filament.admin.auth.password-reset.request'));
            return null;
        }

        try {
            app(PasswordResetOtpService::class)->resetWithOtp(
                email: $email,
                otp: $data['otp'],
                password: $data['password'],
            );

            session()->forget('otp_reset_email');

            Notification::make()
                ->title('Đặt lại mật khẩu thành công!')
                ->body('Vui lòng đăng nhập với mật khẩu mới.')
                ->success()
                ->send();

            $this->redirect(route('filament.admin.auth.login'));
        } catch (ValidationException $e) {
            Notification::make()
                ->title(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();
        }

        return null;
    }
}
