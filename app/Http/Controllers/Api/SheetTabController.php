<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSheetTabRequest;
use App\Http\Requests\Api\UpdateSheetTabRequest;
use App\Http\Resources\SheetTabResource;
use App\Models\SheetSource;
use App\Models\SheetTab;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class SheetTabController extends Controller
{
    public function index(SheetSource $source): AnonymousResourceCollection
    {
        return SheetTabResource::collection(
            $source->tabs()->with('mappings.columns')->orderBy('name')->get(),
        );
    }

    public function store(StoreSheetTabRequest $request, SheetSource $source): JsonResponse
    {
        $tab = $source->tabs()->create($request->validated());

        return (new SheetTabResource($tab->load('mappings.columns')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SheetTab $tab): SheetTabResource
    {
        return new SheetTabResource($tab->load('mappings.columns'));
    }

    public function update(UpdateSheetTabRequest $request, SheetTab $tab): SheetTabResource
    {
        $tab->update($request->validated());

        return new SheetTabResource($tab->refresh()->load('mappings.columns'));
    }

    public function destroy(SheetTab $tab): Response
    {
        $tab->delete();

        return response()->noContent();
    }
}
