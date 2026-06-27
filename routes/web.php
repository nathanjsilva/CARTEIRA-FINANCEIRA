<?php

use Illuminate\Support\Facades\Route;

// Serve Vue SPA for all web routes (Vue Router handles client-side routing)
Route::get('/{any?}', fn () => view('app'))->where('any', '.*');
