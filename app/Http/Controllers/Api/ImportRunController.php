<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ImportRunResource;
use App\Models\ImportRun;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class ImportRunController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $runs = ImportRun::query()
            ->with('entries')
            ->latest('id')
            ->paginate(Pagination::perPage($request));

        return ImportRunResource::collection($runs);
    }

    public function show(ImportRun $run): ImportRunResource
    {
        return new ImportRunResource($run->load('entries'));
    }
}
