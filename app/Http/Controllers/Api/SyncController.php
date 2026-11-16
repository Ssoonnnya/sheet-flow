<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use App\Http\Controllers\Controller;
use App\Http\Resources\ImportRunResource;
use App\Jobs\SyncSheetsJob;
use App\Models\ImportRun;
use Illuminate\Http\JsonResponse;

final class SyncController extends Controller
{
    public function store(): JsonResponse
    {
        $run = ImportRun::query()->create([
            'status' => ImportRunStatus::Pending,
            'trigger' => ImportTrigger::Manual,
        ]);

        SyncSheetsJob::dispatch($run->id);

        return (new ImportRunResource($run))
            ->response()
            ->setStatusCode(202);
    }
}
