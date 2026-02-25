<?php

declare(strict_types=1);

namespace App\Services;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use Illuminate\Auth\Passwords\PasswordBroker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PasswordResetOtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_TTL_MINUTES = 10;
    private const TABLE = 'password_reset_tokens';

    /**
     * Gửi OTP reset password.
     * Dev: log + trả OTP để hiển thị.
     * Production: gửi mail thật, trả null.
     */
    public function sendOtp(string $email): ?string
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Email không hợp lệ hoặc chưa được đăng ký trong hệ thống.'],
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        // Xóa token cũ nếu có
        DB::table(self::TABLE)->where('email', $email)->delete();

        // Lưu OTP hash + thời gian
        DB::table(self::TABLE)->insert([
            'email'      => $email,
            'token'      => Hash::make($otp),
            'created_at' => now(),
        ]);

        if (app()->environment('production')) {
            // Production: gửi mail thật
            Mail::to($user->email)->send(new PasswordResetOtpMail($user->name, $otp));
            return null;
        }

        // Dev/local/testing: log OTP, trả về để show
        \Illuminate\Support\Facades\Log::info("[DEV] OTP cho {$email}: {$otp}");
        return $otp;
    }

    public function resetWithOtp(string $email, string $otp, string $password): void
    {
        $record = DB::table(self::TABLE)->where('email', $email)->first();

        if (!$record) {
            throw ValidationException::withMessages([
                'otp' => ['Không tìm thấy yêu cầu OTP. Vui lòng thử lại từ đầu.'],
            ]);
        }

        // Kiểm tra hết hạn
        $createdAt = \Carbon\Carbon::parse($record->created_at);
        if ($createdAt->addMinutes(self::OTP_TTL_MINUTES)->isPast()) {
            DB::table(self::TABLE)->where('email', $email)->delete();
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP đã hết hạn (10 phút). Vui lòng gửi lại.'],
            ]);
        }

        // Kiểm tra OTP đúng không
        if (!Hash::check($otp, $record->token)) {
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP không đúng.'],
            ]);
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản không tồn tại.'],
            ]);
        }

        // Reset mật khẩu
        $user->update(['password' => Hash::make($password)]);

        // Xóa token sau khi dùng
        DB::table(self::TABLE)->where('email', $email)->delete();
    }
}
