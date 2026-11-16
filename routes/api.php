<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ColumnMappingController;
use App\Http\Controllers\Api\ImportRunController;
use App\Http\Controllers\Api\SheetSourceController;
use App\Http\Controllers\Api\SheetTabController;
use App\Http\Controllers\Api\SyncController;
use App\Http\Controllers\Api\TableMappingController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');
Route::get('/user', [AuthController::class, 'user']);

Route::middleware('auth')->group(function (): void {
    Route::apiResource('sources', SheetSourceController::class);

    Route::scopeBindings()->group(function (): void {
        Route::apiResource('sources.tabs', SheetTabController::class)->shallow();
        Route::apiResource('tabs.mappings', TableMappingController::class)->shallow();
        Route::apiResource('mappings.columns', ColumnMappingController::class)->shallow();
    });

    Route::post('/sync', [SyncController::class, 'store']);
    Route::get('/import-runs', [ImportRunController::class, 'index']);
    Route::get('/import-runs/{run}', [ImportRunController::class, 'show']);
});
