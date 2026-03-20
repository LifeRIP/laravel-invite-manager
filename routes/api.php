<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/invitations/{token}', [InvitationController::class, 'showByToken']);
    Route::post('/invitations/accept', [InvitationController::class, 'accept']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::apiResource('organizations', OrganizationController::class);

        Route::get('/organizations/{organization}/members', [OrganizationController::class, 'members']);
        Route::delete('/organizations/{organization}/members/{user}', [OrganizationController::class, 'removeMember']);
        Route::post('/organizations/{organization}/invitations', [InvitationController::class, 'store']);
    });
});
