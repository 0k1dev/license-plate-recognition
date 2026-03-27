<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Image Driver
    |--------------------------------------------------------------------------
    |
    | Intervention Image supports "gd" and "imagick" drivers by default.
    | We force "gd" here because "imagick" is often missing on hosting.
    |
    */

    'driver' => \Intervention\Image\Drivers\Gd\Driver::class,

];
