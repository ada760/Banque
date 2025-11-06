<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::any('/adminer', function () {
    require_once __DIR__ . '/../vendor/vrana/adminer/adminer-5.4.1.php';
})->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);
