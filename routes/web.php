<?php

use Illuminate\Support\Facades\Route;

// In productie, build-ul de Vue e copiat in public/ si toate rutele din afara API-ului ajung la el.
Route::get('/{any?}', function () {
    $spa = public_path('index.html');

    return file_exists($spa) ? response()->file($spa) : view('welcome');
})->where('any', '(?!api/|sanctum/).*');
