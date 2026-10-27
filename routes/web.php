<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// Main Vue application
Route::get('/', function () {
    return view('welcome');
});

// Display the Vue login screen
Route::get('/login', function () {
    return view('welcome');
});

// Process login credentials
Route::post('/login', [AuthController::class, 'login']);

// Process logout using the existing controller
Route::post('/logout', [AuthController::class, 'logout']);