<?php

namespace Database\Factories;

use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use App\Models\ImportRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRun>
 */
class ImportRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => ImportRunStatus::Pending,
            'trigger' => ImportTrigger::Manual,
            'started_at' => null,
            'finished_at' => null,
            'message' => null,
        ];
    }
}
