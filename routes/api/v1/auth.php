<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API V1 Auth Routes
|--------------------------------------------------------------------------
|
| Here you can define all authentication related routes for API version 1.
| These routes are loaded by the routes/api.php file.
|
*/

Route::prefix('v1/auth')->name('auth.')->group(function () {
    // Public authentication routes (guest middleware)
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register/cafe-owner', [AuthController::class, 'registerCafeOwner'])->name('register.cafe-owner');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login/google', [AuthController::class, 'loginWithGoogle'])->name('login.google');
    Route::post('/login/apple', [AuthController::class, 'loginWithApple'])->name('login.apple');
});
