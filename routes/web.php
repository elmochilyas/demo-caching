<?php

use App\Http\Controllers\CachingDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CachingDemoController::class, 'index'])->name('demo');