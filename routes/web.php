<?php

use Illuminate\Support\Facades\Route;

// API-only application - return 404 for all web routes
Route::fallback(function () {
    abort(404);
});
