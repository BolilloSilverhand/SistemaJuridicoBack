<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CaseEventController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientTransactionController;
use App\Http\Controllers\Api\LegalCaseController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::prefix('legal-cases')->group(function () {
    Route::get('/', [LegalCaseController::class, 'index']);
    Route::post('/', [LegalCaseController::class, 'store']);
    Route::get('/{id}', [LegalCaseController::class, 'show']);
    Route::delete('/{id}', [LegalCaseController::class, 'destroy']);
});

Route::prefix('case-events')->group(function () {
    Route::get('/', [CaseEventController::class, 'index']);
    Route::post('/', [CaseEventController::class, 'store']);
    Route::get('/{id}', [CaseEventController::class, 'show']);
    Route::put('/{id}', [CaseEventController::class, 'update']);
    Route::patch('/{id}', [CaseEventController::class, 'update']);
    Route::delete('/{id}', [CaseEventController::class, 'destroy']);
});

Route::prefix('clients')->group(function () {
    Route::get('/', [ClientController::class, 'index']);
    Route::post('/', [ClientController::class, 'store']);
    Route::get('/{id}', [ClientController::class, 'show']);
    Route::put('/{id}', [ClientController::class, 'update']);
    Route::patch('/{id}', [ClientController::class, 'update']);
    Route::delete('/{id}', [ClientController::class, 'destroy']);
});

Route::prefix('client-transactions')->group(function () {
    Route::post('/', [ClientTransactionController::class, 'store']);
    Route::delete('/{id}', [ClientTransactionController::class, 'destroy']);
});
