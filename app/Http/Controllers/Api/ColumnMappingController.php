<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreColumnMappingRequest;
use App\Http\Requests\Api\UpdateColumnMappingRequest;
use App\Http\Resources\ColumnMappingResource;
use App\Models\ColumnMapping;
use App\Models\TableMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ColumnMappingController extends Controller
{
    public function index(TableMapping $mapping): AnonymousResourceCollection
    {
        return ColumnMappingResource::collection(
            $mapping->columns()->orderBy('destination_column')->get(),
        );
    }

    public function store(StoreColumnMappingRequest $request, TableMapping $mapping): JsonResponse
    {
        $column = $mapping->columns()->create($request->validated());

        return (new ColumnMappingResource($column))
            ->response()
            ->setStatusCode(201);
    }

    public function show(ColumnMapping $column): ColumnMappingResource
    {
        return new ColumnMappingResource($column);
    }

    public function update(UpdateColumnMappingRequest $request, ColumnMapping $column): ColumnMappingResource
    {
        $column->update($request->validated());

        return new ColumnMappingResource($column->refresh());
    }

    public function destroy(ColumnMapping $column): Response
    {
        $column->delete();

        return response()->noContent();
    }
}
