<?php

namespace Database\Factories;

use App\Models\SheetTab;
use App\Models\TableMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TableMapping>
 */
class TableMappingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sheet_tab_id' => SheetTab::factory(),
            'destination_table' => 't_'.strtolower(fake()->unique()->bothify('????????')),
            'unique_columns' => ['date', 'name'],
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
