<?php

use App\Http\Controllers\CachingDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CachingDemoController::class, 'index'])->name('demo');
Route::get('/clear', [CachingDemoController::class, 'clearCache']);
Route::get('/photos-uncached', [CachingDemoController::class, 'photosUncached']);
Route::get('/photos-cached', [CachingDemoController::class, 'photosCached']);
Route::get('/weather-uncached', [CachingDemoController::class, 'weatherUncached']);
Route::get('/weather-cached', [CachingDemoController::class, 'weatherCached']);
