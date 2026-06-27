<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'name'    => 'Carteira Financeira API',
    'version' => '1.0',
    'docs'    => '/api/health',
]));
