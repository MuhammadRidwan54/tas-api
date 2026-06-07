<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TasController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\NotificationController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;



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

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/fcm-token', [NotificationController::class, 'saveFcmToken']);
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
});

Route::get('/users',        [AuthController::class, 'getUsers']);
Route::post('/users',       [AuthController::class, 'createUser']);
Route::delete('/users/{id}',[AuthController::class, 'deleteUser']);

Route::delete(
    '/photo/{id}',
    [TasController::class, 'deletePhoto']
);

Route::get('/app/version', function () {
    return response()->json([
        'version'      => '3.1',  // samakan dengan versionName di build.gradle
        'download_url' => 'https://tas-api-production-7819.up.railway.app/app/BagGallery.apk',
        'notes'        => 'Versi terbaru tersedia'
    ]);
});

Route::post('/users/last-seen', [AuthController::class, 'updateLastSeen']);

Route::get('/health', function () {
    return 'OK';
});

// Route::get('/run-migrations', function() {
//     try {
//         \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
//         return response()->json([
//             'message' => 'Migration success',
//             'output' => \Illuminate\Support\Facades\Artisan::output()
//         ]);
//     } catch (\Exception $e) {
//         return response()->json(['error' => $e->getMessage()], 500);
//     }
// });

Route::get('/debug-notifications', function () {
    try {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['error' => 'No user logged in'], 401);
        }
        
        // Cek tabel notifications
        $hasTable = Schema::hasTable('notifications');
        
        // Cek kolom fcm_token
        $hasFcmColumn = Schema::hasColumn('users', 'fcm_token');
        
        // Hitung notifikasi
        $count = DB::table('notifications')->where('user_id', $user->id)->count();
        
        // Ambil sample notifikasi
        $sample = DB::table('notifications')->where('user_id', $user->id)->first();
        
        return response()->json([
            'user_id' => $user->id,
            'has_notifications_table' => $hasTable,
            'has_fcm_column' => $hasFcmColumn,
            'notifications_count' => $count,
            'sample_notification' => $sample,
            'table_columns' => $hasTable ? Schema::getColumnListing('notifications') : []
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ], 500);
    }
});