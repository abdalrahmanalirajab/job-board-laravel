<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'HireStack API']);
});

Broadcast::routes([
    'prefix' => 'api',
    'middleware' => ['auth:sanctum'],
]);
