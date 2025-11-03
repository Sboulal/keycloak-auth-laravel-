<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\ColisManagment\Http\Controllers\RequestController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/colismanagment', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:api'])->prefix('requests')->group(function () {
    Route::get('prepare-data', [RequestController::class, 'prepareData']);
    Route::post('search-address', [RequestController::class, 'searchAddress']);
    Route::post('save', [RequestController::class, 'save']);
    Route::post('{id}/change-status', [RequestController::class, 'changeStatus']);
    Route::post('{id}/apply-payment', [RequestController::class, 'applyPayment']);
    Route::post('{id}/change-payment-status', [RequestController::class, 'changePaymentStatus']);
    Route::get('list', [RequestController::class, 'loadList']);
    Route::post('search', [RequestController::class, 'search']);
    Route::get('{id}', [RequestController::class, 'show']);
});