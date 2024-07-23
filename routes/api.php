<?php

use App\Http\Controllers\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

# O middleware 'force-json' força a resposta a ser em JSON
# mesmo que o cliente não especifique o cabeçalho Accept como application/json
Route::prefix('v1')->middleware('force-json')->group(function () {
    Route::apiResource('books', BookController::class);
});
