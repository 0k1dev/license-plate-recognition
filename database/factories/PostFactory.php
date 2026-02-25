<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'status' => 'VISIBLE',
            'visible_until' => now()->addDays(30),
            'created_by' => User::factory(),
        ];
    }
}
