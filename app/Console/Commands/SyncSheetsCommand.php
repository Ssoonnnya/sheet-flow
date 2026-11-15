<?php

namespace App\Console\Commands;

use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use App\Jobs\SyncSheetsJob;
use App\Models\ImportRun;
use Illuminate\Console\Command;

final class SyncSheetsCommand extends Command
{
    protected $signature = 'sheets:sync
                            {--scheduled : Mark the run as started by the daily scheduler}';

    protected $description = 'Queue a Google Sheets import run';

    public function handle(): int
    {
        $run = ImportRun::query()->create([
            'status' => ImportRunStatus::Pending,
            'trigger' => $this->option('scheduled')
                ? ImportTrigger::Schedule
                : ImportTrigger::Manual,
        ]);

        SyncSheetsJob::dispatch($run->id);

        $this->info("Queued import run #{$run->id}.");

        return self::SUCCESS;
    }
}
