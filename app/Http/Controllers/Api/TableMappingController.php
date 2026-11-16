<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTableMappingRequest;
use App\Http\Requests\Api\UpdateTableMappingRequest;
use App\Http\Resources\TableMappingResource;
use App\Models\SheetTab;
use App\Models\TableMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class TableMappingController extends Controller
{
    public function index(SheetTab $tab): AnonymousResourceCollection
    {
        return TableMappingResource::collection(
            $tab->mappings()->with('columns')->orderBy('destination_table')->get(),
        );
    }

    public function store(StoreTableMappingRequest $request, SheetTab $tab): JsonResponse
    {
        $mapping = $tab->mappings()->create($request->validated());

        return (new TableMappingResource($mapping->load('columns')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(TableMapping $mapping): TableMappingResource
    {
        return new TableMappingResource($mapping->load('columns'));
    }

    public function update(UpdateTableMappingRequest $request, TableMapping $mapping): TableMappingResource
    {
        $mapping->update($request->validated());

        return new TableMappingResource($mapping->refresh()->load('columns'));
    }

    public function destroy(TableMapping $mapping): Response
    {
        $mapping->delete();

        return response()->noContent();
    }
}
