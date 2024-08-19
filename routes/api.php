<?php

use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckUser;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::group(
    ['namespace' => 'Api'],
    function () {
        Route::any('/login', [LoginController::class, 'login']);
        Route::any('/contact', [LoginController::class, 'contact'])->middleware(CheckUser::class);
    },
);

// routes/api.php
Route::middleware('api')->group(function () {
    Route::post('forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('reset-password', [ResetPasswordController::class, 'reset']);
});
