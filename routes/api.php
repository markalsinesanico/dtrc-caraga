<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;

Route::get(
    '/inventory/registration-history',
    [InventoryController::class, 'registrationHistory']
);

Route::get(
    '/inventory/request-history',
    [InventoryController::class, 'requestHistory']
);

Route::post(
    '/inventory/requests',
    [InventoryController::class, 'requestItem']
);

Route::apiResource(
    'inventory',
    InventoryController::class
);