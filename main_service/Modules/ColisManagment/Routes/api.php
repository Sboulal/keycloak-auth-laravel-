<?php

use Illuminate\Support\Facades\Route;
use Modules\ColisManagment\Http\Controllers\RequestController;
use Modules\ColisManagment\Http\Controllers\CrudRequestController;

// Pour environnement local uniquement
if (config('app.fake_auth_enabled')) {
    Route::middleware(['fake.auth'])->prefix('requests')->group(function () {
     // Préparer les données pour créer une demande
        Route::get('/prepare-data', [RequestController::class, 'prepareRequestData']);
        
        // Rechercher une adresse
        Route::post('/search-address', [RequestController::class, 'searchAddress']);
        
        // CRUD des demandes
       // CRUD
    Route::get('/', [RequestController::class, 'loadRequestsList']);
    Route::post('/', [CrudRequestController::class, 'saveRequestData']);
    Route::get('/{id}', [RequestController::class, 'show']);
    Route::put('/{id}', [CrudRequestController::class, 'saveRequestData']);
    Route::delete('/{id}', [CrudRequestController::class, 'destroy']);
        
        // Recherche
        Route::post('/search', [RequestController::class, 'searchRequest']);
        
        // Changer le statut
        Route::put('/{id}/status', [RequestController::class, 'changeStatus']);
        
        // Routes admin uniquement
        Route::middleware(['role:admin,manager'])->group(function () {
            // Endpoints admin spécifiques si nécessaire
        });
    });
} else {
    // Routes normales avec auth:api
    Route::middleware(['auth:api'])->prefix('requests')->group(function () {
        // Préparer les données
    Route::get('/prepare-data', [RequestController::class, 'prepareRequestData']);
    
    // Rechercher une adresse
    Route::post('/search-address', [RequestController::class, 'searchAddress']);
    
    // CRUD
    Route::get('/', [RequestController::class, 'loadRequestsList']);
    Route::post('/', [CrudRequestController::class, 'saveRequestData']);
    Route::get('/{id}', [RequestController::class, 'show']);
    Route::put('/{id}', [CrudRequestController::class, 'saveRequestData']);
    Route::delete('/{id}', [CrudRequestController::class, 'destroy']);
    
    // Recherche
    Route::post('/search', [CrudRequestController::class, 'searchRequest']);
    
    // Actions admin
    Route::middleware(['role:admin,manager'])->group(function () {
        Route::put('/{id}/status', [RequestController::class, 'changeRequestStatus']);
        Route::post('/{id}/payment', [RequestController::class, 'applyPayment']);
        Route::put('/{id}/payment-status', [RequestController::class, 'changePaymentStatus']);
    });
});
}