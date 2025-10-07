<?php

use App\Http\Controllers\BasicInfoController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::apiResource('resumes', ResumeController::class);
    Route::apiResource('basic-info', BasicInfoController::class);
    Route::get('/my-resumes', [UserController::class, 'myResumes']);
});


