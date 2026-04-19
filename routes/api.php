<?php

use App\Http\Controllers\CachingDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/photos-uncached', [CachingDemoController::class, 'photosUncached']);
Route::get('/photos-cached', [CachingDemoController::class, 'photosCached']);
Route::get('/weather-uncached', [CachingDemoController::class, 'weatherUncached']);
Route::get('/weather-cached', [CachingDemoController::class, 'weatherCached']);
Route::post('/clear-cache', [CachingDemoController::class, 'clearCache']);