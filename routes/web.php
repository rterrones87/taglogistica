<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

// Algunos hostings no conservan el enlace simbólico public/storage al desplegar.
// Servir estas evidencias desde el disco público evita que la ruta SPA devuelva
// app.blade.php (HTML) cuando se solicita una imagen existente.
Route::get('/storage/evidencias/{filename}', function (string $filename) {
    $path = 'evidencias/'.$filename;

    abort_unless(Storage::disk('public')->exists($path), 404);

    return response()->file(Storage::disk('public')->path($path), [
        'Cache-Control' => 'public, max-age=86400',
    ]);
})->where('filename', '[A-Za-z0-9_-]+\.(?:jpe?g|png|webp)');

Route::get('/{any}', function () {
    return view('app'); 
})->where('any', '.*');
