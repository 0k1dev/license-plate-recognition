<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthChangePasswordRequest;
use App\Http\Requests\AuthForgotPasswordRequest;
use App\Http\Requests\AuthLoginRequest;
use App\Http\Requests\AuthResetPasswordRequest;
use App\Http\Requests\AuthUpdateProfileRequest;
use App\Http\Requests\AuthVerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(AuthLoginRequest $request)
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email hoặc mật khẩu không chính xác.'],
            ]);
        }

        if ($user->is_locked) {
            throw ValidationException::withMessages([
                'email' => ['Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.'],
            ]);
        }

        $accessExpiry = now()->addDay();
        $refreshExpiry = ($data['remember'] ?? false) ? now()->addDays(30) : now()->addDays(1);

        $deviceName = $data['device_name'] ?? 'Unknown Device';

        $accessToken = $user->createToken(
            $deviceName . '_access',
            ['access-api'],
            $accessExpiry
        );

        $refreshToken = $user->createToken(
            $deviceName . '_refresh',
            ['issue-access-token'],
            $refreshExpiry
        );

        return response()->json([
            'message' => 'Đăng nhập thành công!',
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
            'expires_in' => 86400,
            'user' => new UserResource($user),
        ]);
    }

    public function refresh(Request $request)
    {
        $user = $request->user();

        if (! $user->currentAccessToken()->can('issue-access-token')) {
            return response()->json(['message' => 'Token không hợp lệ để refresh.'], 403);
        }

        $request->user()->currentAccessToken()->delete();

        $refreshExpiry = now()->addDays(30);
        $accessExpiry = now()->addDay();

        $newAccessToken = $user->createToken(
            'access_token_refreshed',
            ['access-api'],
            $accessExpiry
        );

        $newRefreshToken = $user->createToken(
            'refresh_token_rotated',
            ['issue-access-token'],
            $refreshExpiry
        );

        return response()->json([
            'access_token' => $newAccessToken->plainTextToken,
            'refresh_token' => $newRefreshToken->plainTextToken,
            'expires_in' => 86400,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Đăng xuất thành công.']);
    }

    public function forgotPassword(AuthForgotPasswordRequest $request)
    {
        $otp = rand(100000, 999999);
        $email = $request->validated()['email'];

        \Illuminate\Support\Facades\Cache::put('otp_reset_' . $email, $otp, now()->addMinutes(5));

        $response = [
            'message' => 'Mã OTP đã được gửi đến email của bạn. Vui lòng kiểm tra hộp thư.',
            'expires_in' => 300,
        ];

        if (app()->environment('local', 'development')) {
            $response['otp'] = $otp;
            $response['_dev_note'] = 'OTP is only shown in development mode';
        }

        return response()->json($response);
    }

    public function verifyOtp(AuthVerifyOtpRequest $request)
    {
        $data = $request->validated();

        $cachedOtp = \Illuminate\Support\Facades\Cache::get('otp_reset_' . $data['email']);

        if (! $cachedOtp || $cachedOtp != $data['otp']) {
            throw ValidationException::withMessages([
                'otp' => ['Mã OTP không chính xác hoặc đã hết hạn.'],
            ]);
        }

        $resetToken = \Illuminate\Support\Str::random(64);
        \Illuminate\Support\Facades\Cache::put(
            'reset_token_' . $resetToken,
            $data['email'],
            now()->addMinutes(15)
        );

        return response()->json([
            'message' => 'Xác thực OTP thành công.',
            'reset_token' => $resetToken,
            'expires_in' => 900,
        ]);
    }

    public function resetPassword(AuthResetPasswordRequest $request)
    {
        $data = $request->validated();

        if ($request->filled('reset_token')) {
            $cachedEmail = \Illuminate\Support\Facades\Cache::get('reset_token_' . $data['reset_token']);
            if (! $cachedEmail || $cachedEmail !== $data['email']) {
                return response()->json([
                    'message' => 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.'
                ], 401);
            }
        } elseif ($request->filled('otp')) {
            $cachedOtp = \Illuminate\Support\Facades\Cache::get('otp_reset_' . $data['email']);
            if (! $cachedOtp || $cachedOtp != $data['otp']) {
                return response()->json([
                    'message' => 'Mã OTP không chính xác hoặc đã hết hạn.'
                ], 401);
            }
        } else {
            return response()->json([
                'message' => 'Vui lòng cung cấp reset_token hoặc otp.'
            ], 422);
        }

        $user = User::where('email', $data['email'])->first();
        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        if ($request->filled('reset_token')) {
            \Illuminate\Support\Facades\Cache::forget('reset_token_' . $data['reset_token']);
        }
        \Illuminate\Support\Facades\Cache::forget('otp_reset_' . $data['email']);

        \App\Models\AuditLog::log('reset_password', User::class, $user->id);

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập lại.'
        ]);
    }

    public function changePassword(AuthChangePasswordRequest $request)
    {
        $data = $request->validated();

        $user = $request->user();

        if (! Hash::check($data['old_password'], $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => ['Mật khẩu cũ không chính xác.'],
            ]);
        }

        $user->update([
            'password' => Hash::make($data['new_password']),
        ]);

        \App\Models\AuditLog::log('change_password', User::class, $user->id);

        return response()->json(['message' => 'Đổi mật khẩu thành công.']);
    }

    public function updateProfile(AuthUpdateProfileRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        $user->update($validated);

        return response()->json([
            'message' => 'Cập nhật thông tin thành công.',
            'data' => new UserResource($user),
        ]);
    }
}
