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
            'reportable_type' => Post::class,
            'reportable_id' => Post::factory(),
            'reporter_id' => User::factory(),
            'type' => 'SPAM',
            'content' => $this->faker->paragraph(),
            'status' => 'OPEN',
        ];
    }
}
