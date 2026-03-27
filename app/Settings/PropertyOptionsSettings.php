<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PropertyOptionsSettings extends Settings
{
    public array $amenities;
    public array $directions;
    public array $shapes;
    public array $location_types;
    public array $legal_statuses;

    public static function group(): string
    {
        return 'property_options';
    }
}
