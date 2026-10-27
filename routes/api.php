
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InventoryController;

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

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

    Route::apiResource('inventory', InventoryController::class);
});
