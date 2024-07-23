<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;


# O middleware 'force-json' força a resposta a ser em JSON
# mesmo que o cliente não especifique o cabeçalho Accept como application/json
Route::middleware(['auth:api', 'force-json'])->prefix('v1')->group(function () {
    Route::prefix('auth')->controller(AuthController::class)->group(function () {
        Route::withoutMiddleware('auth:api')->post('login', 'login');
        Route::post('logout', 'logout');
    });

    # Usado API Resource para criar rotas CRUD para o BookController
    # Agilizando a criação de rotas para o CRUD
    Route::apiResource('books', BookController::class);
});

