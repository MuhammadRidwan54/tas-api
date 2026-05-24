<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TasController;
use App\Http\Controllers\Api\AuthController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::post('/login', [
    AuthController::class,
    'login'
]);


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/tas', [TasController::class, 'index']);
Route::post('/tas', [TasController::class, 'store']);
Route::get('/tas/{id}', [TasController::class, 'show']);
Route::put('/tas/{id}', [TasController::class, 'update']);
Route::delete('/tas/{id}', [TasController::class, 'destroy']);

Route::post(
    '/tas/{id}/photo',
    [TasController::class, 'addPhoto']
);

Route::get('/users',        [AuthController::class, 'getUsers']);
Route::post('/users',       [AuthController::class, 'createUser']);
Route::delete('/users/{id}',[AuthController::class, 'deleteUser']);

Route::delete(
    '/photo/{id}',
    [TasController::class, 'deletePhoto']
);

Route::get('/health', function () {
    return 'OK';
});
