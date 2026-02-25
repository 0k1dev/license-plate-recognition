<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Illuminate\Support\Facades\Log;

class DebugLogin extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';
    protected static string $layout = 'filament-panels::components.layout.base';

    public function authenticate(): ?LoginResponse
    {
        try {
            $response = parent::authenticate();
            return $response;
        } catch (\Exception $e) {
            Log::error('LOGIN ERROR: ' . $e->getMessage());

            // FIX: Authenticate thành công (session đã có), trả về LoginResponse chuẩn
            if (auth()->check()) {
                Log::warning('LOGIN: Authentication OK. Skipping secondary errors.');
                return app(LoginResponse::class);
            }

            throw $e;
        }
    }

    public function getBg(): ?string
    {
        try {
            $settings = app(\App\Settings\GeneralSettings::class);
            if ($settings->auth_bg_image) {
                return \Illuminate\Support\Facades\Storage::url($settings->auth_bg_image);
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function getSiteName(): string
    {
        try {
            return app(\App\Settings\GeneralSettings::class)->site_name ?? 'Bất động sản';
        } catch (\Throwable) {
            return 'Bất động sản';
        }
    }

    public function getLogo(): ?string
    {
        try {
            $settings = app(\App\Settings\GeneralSettings::class);
            return $settings->site_logo ? \Illuminate\Support\Facades\Storage::url($settings->site_logo) : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
