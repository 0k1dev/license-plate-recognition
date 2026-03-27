<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'reportable_type' => Post::class,
            'reportable_id' => fn(array $attributes) => $attributes['post_id'],
            'reporter_id' => User::factory(),
            'type' => 'SPAM',
            'content' => $this->faker->paragraph(),
            'status' => 'OPEN',
        ];
    }
}
