<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImportRunResource;
use App\Models\ImportRun;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ImportRunController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $runs = ImportRun::query()
            ->with('entries')
            ->latest('id')
            ->paginate(20);

        return ImportRunResource::collection($runs);
    }

    public function show(ImportRun $run): ImportRunResource
    {
        return new ImportRunResource($run->load('entries'));
    }
}
