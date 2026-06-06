<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TasController;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Artisan;



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

Route::get('/app/version', function () {
    return response()->json([
        'version'      => '1.1',  // samakan dengan versionName di build.gradle
        'download_url' => 'https://tas-api-production-7819.up.railway.app/app/BagGallery.apk',
        'notes'        => 'Versi terbaru tersedia'
    ]);
});

Route::post('/users/last-seen', [AuthController::class, 'updateLastSeen']);

Route::get('/health', function () {
    return 'OK';
});

// ENDPOINT KHUSUS UNTUK MEMPERBAIKI ERROR 500 - HAPUS SETELAH SUKSES
Route::get('/fix-role-migration', function() {
    try {
        // Jalankan hanya migrasi untuk file add_role_to_users_table
        \Illuminate\Support\Facades\Artisan::call('migrate --force --path=database/migrations/2026_05_20_152423_add_role_to_users_table.php');
        return response()->json([
            'status' => 'Migration executed',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});