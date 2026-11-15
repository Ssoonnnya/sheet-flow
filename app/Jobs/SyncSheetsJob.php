<?php

namespace App\Jobs;

use App\Models\ImportRun;
use App\Services\Sheets\SyncSheets;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

final class SyncSheetsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $importRunId,
    ) {}

    public function uniqueId(): string
    {
        return 'sync-sheets';
    }

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('sync-sheets'))
                ->expireAfter(3600)
                ->dontRelease(),
        ];
    }

    public function handle(SyncSheets $sync): void
    {
        $run = ImportRun::query()->find($this->importRunId);

        if ($run === null) {
            return;
        }

        $sync->process($run);
    }
}
