<?php

namespace Database\Factories;

use App\Models\SheetSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SheetSource>
 */
class SheetSourceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'spreadsheet_id' => fake()->unique()->bothify('????????????????'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
