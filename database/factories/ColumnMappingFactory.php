<?php

namespace Database\Factories;

use App\Enums\ColumnType;
use App\Models\ColumnMapping;
use App\Models\TableMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ColumnMapping>
 */
class ColumnMappingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $column = strtolower(fake()->unique()->lexify('????????'));

        return [
            'table_mapping_id' => TableMapping::factory(),
            'source_column' => $column,
            'destination_column' => $column,
            'data_type' => ColumnType::String,
        ];
    }
}
