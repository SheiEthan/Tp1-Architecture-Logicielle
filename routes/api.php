<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserApiController;
Route::apiResource('users', UserApiController::class);
