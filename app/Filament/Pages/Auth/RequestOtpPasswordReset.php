<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Services\PasswordResetOtpService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Validation\ValidationException;

class RequestOtpPasswordReset extends BaseRequestPasswordReset
{
    use WithRateLimiting;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('email')
                    ->label('Email đăng ký')
                    ->email()
                    ->required()
                    ->autocomplete('username')
                    ->autofocus()
                    ->placeholder('name@company.com'),
            ]);
    }

    public function request(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title("Quá nhiều yêu cầu. Vui lòng thử lại sau {$exception->secondsUntilAvailable} giây.")
                ->danger()
                ->send();
            return;
        }

        $data = $this->form->getState();

        try {
            $otp = app(PasswordResetOtpService::class)->sendOtp($data['email']);

            // Lưu email vào session để dùng ở trang nhập OTP
            session(['otp_reset_email' => $data['email']]);

            $body = $otp
                ? "Mã OTP của bạn: **{$otp}** (hiệu lực 10 phút)"
                : 'Kiểm tra hộp thư của bạn. Mã có hiệu lực 10 phút.';

            Notification::make()
                ->title('Mã OTP đã được gửi!')
                ->body($body)
                ->success()
                ->send();

            $this->redirect(\Illuminate\Support\Facades\URL::temporarySignedRoute(
                'filament.admin.auth.password-reset.reset',
                now()->addMinutes(10)
            ));
        } catch (ValidationException $e) {
            $this->form->fill();
            Notification::make()
                ->title(collect($e->errors())->flatten()->first())
                ->danger()
                ->send();
        }
    }
}
