<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

// GraphQL endpoint is automatically handled by Lighthouse
// Default endpoint: POST /graphql
// Playground: GET /graphql

// Health check endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'ms-autentificacion',
        'timestamp' => now(),
    ]);
});

// API version endpoint
Route::get('/version', function () {
    return response()->json([
        'version' => config('app.version', '1.0.0'),
        'service' => 'ms-autentificacion',
    ]);
});
