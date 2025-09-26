<?php

use Illuminate\Support\Facades\Route;

use Presentation\UserApiController;
Route::apiResource('users', UserApiController::class);
