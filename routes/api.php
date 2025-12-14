<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// All API routes require authentication
// Unauthenticated requests will receive 404 instead of 401
Route::middleware(['auth:sanctum', 'return404'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Add your other API routes here
    // They will all require authentication and return 404 if not authenticated
});
