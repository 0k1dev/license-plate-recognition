<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    // Minimal Login - không custom gì để test
    // Nếu login OK với version này → custom methods gây lỗi
}
