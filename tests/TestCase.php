<?php

declare(strict_types=1);

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Mock API Key config
        config(['api.allowed_keys' => ['test-api-key']]);
    }

    /**
     * Authenticate as a user for API requests with necessary headers
     */
    public function actingAsApi(User $user)
    {
        // Set the API key header for subsequent requests
        $this->withHeaders(['X-API-KEY' => 'test-api-key', 'Accept' => 'application/json']);

        // Authenticate with sanctum guard
        return $this->actingAs($user, 'sanctum');
    }
}
