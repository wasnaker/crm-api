<?php

use Illuminate\Support\Facades\Route;

/**
 * The API host serves no HTML. The frontend lives in crm-web and is served
 * separately, so there is no view to render here.
 *
 * The root path answers with a small service descriptor rather than a 404 so
 * that a browser, an uptime probe or a stray curl gets a pointer to the real
 * entry points instead of a stack trace.
 */
Route::get('/', function () {
    return response()->json([
        'service' => config('app.name'),
        'api' => '/api/v1',
        'health' => '/api/v1/health',
        'docs' => '/docs/api',
    ]);
});
