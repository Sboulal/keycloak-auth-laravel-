<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\TrackaddressController;

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

Route::prefix('tracking')->group(function () {
    
    // Géolocalisation et adresses
    Route::get('/search-address', [TrackaddressController::class, 'searchAddress']);
    Route::get('/reverse-geocode', [TrackaddressController::class, 'reverseGeocode']);
    Route::get('/route', [TrackaddressController::class, 'getRoute']);
    
    // Gestion des livreurs
    Route::get('/active-drivers', [TrackaddressController::class, 'getActiveDrivers']);
    Route::post('/driver/{driverId}/toggle-online', [TrackaddressController::class, 'toggleDriverOnline']);
    
    // Tracking en temps réel
    Route::post('/record-point', [TrackaddressController::class, 'recordTrackingPoint']);
    
    // Gestion des livraisons
    Route::get('/delivery/{deliveryId}', [TrackaddressController::class, 'getDeliveryTracking']);
    Route::post('/delivery/{deliveryId}/start', [TrackaddressController::class, 'startDelivery']);
    Route::post('/delivery/{deliveryId}/complete', [TrackaddressController::class, 'completeDelivery']);
});