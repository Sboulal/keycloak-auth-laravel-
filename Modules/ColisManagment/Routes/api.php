<?php

use Illuminate\Support\Facades\Route;
use Modules\ColisManagment\Http\Controllers\RequestController;
use Modules\ColisManagment\Http\Controllers\CrudRequestController;

// Pour environnement local uniquement
if (config('app.fake_auth_enabled')) {
    Route::middleware(['fake.auth'])->prefix('requests')->group(function () {
        // Préparer les données pour créer une demande
        Route::get('/prepare-data', [RequestController::class, 'prepareRequestData']);
        
        // Recherche - MUST come before other POST routes
        Route::post('/search', [CrudRequestController::class, 'searchRequest']);
        
        // Liste des demandes
        Route::get('/', [RequestController::class, 'loadRequestsList']);
        
        // Créer une demande (POST only for creation)
        Route::post('/', [CrudRequestController::class, 'store']);
        
        // Détails d'une demande
        Route::get('/{id}', [RequestController::class, 'show'])->where('id', '[0-9]+');
        
        // Mettre à jour une demande (PUT with ID in URL)
        Route::put('/{id}', [CrudRequestController::class, 'update'])->where('id', '[0-9]+');
        
        // Changer le statut
        Route::patch('/{id}/status', [CrudRequestController::class, 'changeStatus'])->where('id', '[0-9]+');
        
        // Supprimer une demande
        Route::delete('/{id}', [CrudRequestController::class, 'destroy'])->where('id', '[0-9]+');
        
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
        
        // Recherche - comes first
        Route::post('/search', [CrudRequestController::class, 'searchRequest']);
        
        // CRUD
        Route::get('/', [RequestController::class, 'loadRequestsList']);
        Route::post('/', [CrudRequestController::class, 'store']);
        Route::get('/{id}', [RequestController::class, 'show'])->where('id', '[0-9]+');
        Route::put('/{id}', [CrudRequestController::class, 'update'])->where('id', '[0-9]+');
        Route::delete('/{id}', [CrudRequestController::class, 'destroy'])->where('id', '[0-9]+');
        
        // Actions admin
        Route::middleware(['role:admin,manager'])->group(function () {
            Route::patch('/{id}/status', [CrudRequestController::class, 'changeStatus'])->where('id', '[0-9]+');
            // Add other admin routes if needed
        });
    });
}