<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\IngestController;
use App\Http\Controllers\Api\StatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Health check endpoint (no rate limiting)
Route::get('/health', HealthController::class)->name('api.health');

// Log ingestion endpoint with rate limiting (1000 requests per minute per IP)
Route::post('/ingest', IngestController::class)
    ->middleware(['throttle:1000,1', 'rate.headers:1000,1'])
    ->name('api.ingest');

// Server stats endpoint
Route::post('/stats', StatsController::class)
    ->middleware(['throttle:60,1', 'rate.headers:60,1'])
    ->name('api.stats');
