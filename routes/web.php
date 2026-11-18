<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'application' => 'WARDES API',
    'status' => 'running',
    'api' => '/api/v1',
]));
