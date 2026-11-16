<?php

namespace App\Http\Controllers\Api;

use App\Enums\ImportRunStatus;
use App\Enums\ImportTrigger;
use App\Exceptions\SheetFetchException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSheetSourceRequest;
use App\Http\Requests\Api\UpdateSheetSourceRequest;
use App\Http\Resources\SheetSourceResource;
use App\Jobs\SyncSheetsJob;
use App\Models\ImportRun;
use App\Models\SheetSource;
use App\Services\Sheets\ProvisionSheetSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Throwable;

final class SheetSourceController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $sources = SheetSource::query()
            ->with(['tabs.mappings.columns'])
            ->orderBy('name')
            ->get();

        return SheetSourceResource::collection($sources);
    }

    public function store(
        StoreSheetSourceRequest $request,
        ProvisionSheetSource $provisioner,
    ): JsonResponse {
        $validated = $request->validated();

        try {
            $source = $provisioner->provision(
                $validated['spreadsheet_id'],
                isset($validated['name']) && is_string($validated['name']) ? $validated['name'] : null,
            );
        } catch (SheetFetchException $exception) {
            throw ValidationException::withMessages([
                'spreadsheet_id' => $exception->clientMessage(),
            ]);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'spreadsheet_id' => $exception->getMessage(),
            ]);
        } catch (Throwable $exception) {
            throw ValidationException::withMessages([
                'spreadsheet_id' => 'Could not read this Google Sheet. Check sharing and the Sheets API.',
            ]);
        }

        $run = ImportRun::query()->create([
            'status' => ImportRunStatus::Pending,
            'trigger' => ImportTrigger::Manual,
        ]);

        SyncSheetsJob::dispatch($run->id);

        return (new SheetSourceResource($source->load(['tabs.mappings.columns'])))
            ->additional([
                'meta' => [
                    'import_run_id' => $run->id,
                ],
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(SheetSource $source): SheetSourceResource
    {
        return new SheetSourceResource($source->load(['tabs.mappings.columns']));
    }

    public function update(UpdateSheetSourceRequest $request, SheetSource $source): SheetSourceResource
    {
        $source->update($request->validated());

        return new SheetSourceResource($source->refresh()->load(['tabs.mappings.columns']));
    }

    public function destroy(SheetSource $source): Response
    {
        $source->delete();

        return response()->noContent();
    }
}
