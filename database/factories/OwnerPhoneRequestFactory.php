<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\OwnerPhoneRequest;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OwnerPhoneRequestFactory extends Factory
{
    protected $model = OwnerPhoneRequest::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'requester_id' => User::factory(),
            'status' => 'PENDING',
            'reason' => $this->faker->sentence(),
        ];
    }
}
