<?php

namespace Database\Factories;

use App\Models\Property;
use App\Models\User;
use App\Models\Area;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    protected $model = Property::class;

    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(1000000, 10000000000),
            'area' => $this->faker->numberBetween(30, 500),
            'address' => $this->faker->address(),
            'owner_name' => $this->faker->name(),
            'owner_phone' => $this->faker->phoneNumber(),
            'approval_status' => 'PENDING',
            'created_by' => User::factory(),
            'category_id' => Category::factory(), // Assuming Category factory exists, else create manually in test
            'area_id' => Area::factory(),         // Assuming Area factory exists, else create manually in test
        ];
    }

    public function approved(): static
    {
        return $this->state(fn(array $attributes) => [
            'approval_status' => 'APPROVED',
            'approved_at' => now(),
            'approved_by' => User::factory(),
        ]);
    }
}
