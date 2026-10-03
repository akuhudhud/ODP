<?php

use App\Http\Controllers\Api\V1\Account\AccountController;
use App\Http\Controllers\Api\V1\Account\ProfileController;
use App\Http\Controllers\Api\V1\Auth\LoginController;
use App\Http\Controllers\Api\V1\Auth\LogoutController;
use App\Http\Controllers\Api\V1\HealthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', HealthController::class);

    Route::post('/auth/login', LoginController::class);

    Route::middleware('api.session')->group(function () {
        Route::get('/account', AccountController::class);

        Route::get('/account/profile', ProfileController::class);

        Route::post('/auth/logout', LogoutController::class);
    });
});
