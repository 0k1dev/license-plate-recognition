<?php

declare(strict_types=1);
namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Support\Facades\Log;

class DebugLogin extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        Log::info('DEBUG LOGIN: Bắt đầu authenticate');

        try {
            $response = parent::authenticate();

            Log::info('DEBUG LOGIN: Authenticate thành công!');

            // Check response type
            if ($response instanceof \Illuminate\Http\RedirectResponse) {
                Log::info('DEBUG LOGIN: Redirecting to -> ' . $response->getTargetUrl());
            } else {
                Log::info('DEBUG LOGIN: Response Class -> ' . get_class($response));
            }

            return $response;
            return $response;
        } catch (\Exception $e) {
            Log::error('DEBUG LOGIN: Lỗi -> ' . $e->getMessage());

            // FIX: Authenticate thành công (session đã có), trả về LoginResponse chuẩn
            if (auth()->check()) {
                Log::warning('DEBUG LOGIN: Authentication OK. Skipping secondary errors.');
                return app(LoginResponse::class);
            }

            throw $e;
        }
    }
}
