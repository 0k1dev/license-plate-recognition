<?php

declare(strict_types=1);
namespace App\Traits;

trait HasUserMenuPreferences
{
    public static function shouldRegisterNavigation(): bool
    {
        $hiddenResources = config('user_menu.hidden_resources', []);

        if (in_array(static::class, $hiddenResources)) {
            return false;
        }

        return parent::shouldRegisterNavigation();
    }
}
