<?php

declare(strict_types=1);
namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;
    public ?string $site_logo = null;
    public ?string $auth_bg_image = null;

    public static function group(): string
    {
        return 'general';
    }
}
