<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/inventory', [InventoryController::class, 'index']);
Route::post('/inventory', [InventoryController::class, 'store']);
Route::get('/inventory/{inventory}', [InventoryController::class, 'show']);
Route::put('/inventory/{inventory}', [InventoryController::class, 'update']);
Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy']);