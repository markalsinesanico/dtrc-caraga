<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\AccountController;

Route::middleware('auth:sanctum')->group(function () {

    // Current logged-in user
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Manage Office Accounts - Admin only
    Route::get('/accounts', [AccountController::class, 'index']);
    Route::post('/accounts', [AccountController::class, 'store']);

    // Inventory viewing - Admin and Office
    Route::get('/inventory', [InventoryController::class, 'index']);

    Route::get('/inventory/registration-history', [InventoryController::class, 'registrationHistory']);

    Route::get('/inventory/request-history', [InventoryController::class, 'requestHistory']);

    Route::post('/inventory/requests', [InventoryController::class, 'requestItem']);

    Route::post('/inventory', [InventoryController::class, 'store']);

    Route::get('/inventory/{inventory}', [InventoryController::class, 'show'])
        ->whereNumber('inventory');

    Route::put('/inventory/{inventory}', [InventoryController::class, 'update'])
        ->whereNumber('inventory');

    Route::patch('/inventory/{inventory}', [InventoryController::class, 'update'])
        ->whereNumber('inventory');

    Route::delete('/inventory/{inventory}', [InventoryController::class, 'destroy'])
        ->whereNumber('inventory');
});