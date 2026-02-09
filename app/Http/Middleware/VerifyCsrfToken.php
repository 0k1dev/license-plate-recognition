<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * Livewire handles its own CSRF protection via headers,
     * so we exclude it here to prevent double-checking.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Livewire endpoints handle CSRF via X-CSRF-TOKEN header
        'livewire/update',
        'livewire/upload-file',
    ];
}
