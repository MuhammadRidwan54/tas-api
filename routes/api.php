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
*/

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/tas', [TasController::class, 'index']);
Route::post('/tas', [TasController::class, 'store']);
Route::get('/tas/{id}', [TasController::class, 'show']);
Route::put('/tas/{id}', [TasController::class, 'update']);
Route::delete('/tas/{id}', [TasController::class, 'destroy']);

Route::post('/tas/{id}/photo', [TasController::class, 'addPhoto']);

// Route::middleware('auth:sanctum')->group(function () {
    Route::post('/fcm-token', [NotificationController::class, 'saveFcmToken']);
    Route::get('/notifications', [NotificationController::class, 'getNotifications']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);

    // TAMBAHKAN INI - endpoint test auth sederhana
    Route::get('/auth-test', function () {
        return response()->json([
            'user' => Auth::user(),
            'message' => 'Auth berhasil!'
        ]);
    });
// });

Route::get('/users', [AuthController::class, 'getUsers']);
Route::post('/users', [AuthController::class, 'createUser']);
Route::delete('/users/{id}', [AuthController::class, 'deleteUser']);

Route::delete('/photo/{id}', [TasController::class, 'deletePhoto']);

Route::get('/app/version', function () {
    return response()->json([
        'version'      => '3.1',
        'download_url' => 'https://tas-api-production-7819.up.railway.app/app/BagGallery.apk',
        'notes'        => 'Versi terbaru tersedia'
    ]);
});

Route::post('/users/last-seen', [AuthController::class, 'updateLastSeen']);

Route::get('/health', function () {
    return 'OK';
});

// ============================================================
// DEBUG ENDPOINTS (TANPA AUTH) - HAPUS SETELAH SELESAI
// ============================================================

// Test 1: Cek tabel dan data notifikasi
Route::get('/test-notifications', function () {
    try {
        $hasTable = Schema::hasTable('notifications');
        
        if (!$hasTable) {
            return response()->json([
                'error' => 'Tabel notifications tidak ditemukan',
                'hint' => 'Jalankan php artisan migrate'
            ], 500);
        }
        
        $notifications = DB::table('notifications')->get();
        $columns = Schema::getColumnListing('notifications');
        
        return response()->json([
            'success' => true,
            'table_exists' => true,
            'columns' => $columns,
            'notifications_count' => $notifications->count(),
            'notifications' => $notifications
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ], 500);
    }
});

// Test 2: Buat notifikasi baru
Route::get('/test-create-notification', function () {
    try {
        $id = DB::table('notifications')->insertGetId([
            'user_id' => 2,
            'sender_id' => 2,
            'title' => 'Test Notification',
            'message' => 'Ini adalah test notifikasi',
            'type' => 'info',
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return response()->json([
            'success' => true,
            'notification_id' => $id
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'line' => $e->getLine()
        ], 500);
    }
});

// Test 3: Debug auth (tanpa middleware)
Route::get('/debug-auth', function () {
    try {
        // Cek konfigurasi sanctum
        $guard = config('sanctum.guard', 'web');
        $stateful = config('sanctum.stateful', []);
        
        return response()->json([
            'php_version' => PHP_VERSION,
            'sanctum_guard' => $guard,
            'sanctum_stateful' => $stateful,
            'has_sanctum_middleware' => true
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/clear-cache', function() {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('optimize:clear');
    return 'Cache cleared';
});

Route::get('/ping', function () {
    return 'pong';
});

Route::get('/migrate-sanctum', function() {
    try {
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return response()->json([
            'message' => 'Migration success',
            'output' => \Illuminate\Support\Facades\Artisan::output()
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});

Route::get('/check-sanctum-table', function() {
    $hasTable = Schema::hasTable('personal_access_tokens');
    return response()->json([
        'personal_access_tokens_table_exists' => $hasTable
    ]);
});

Route::get('/clear-tokens', function() {
    DB::table('personal_access_tokens')->truncate();
    return 'All tokens cleared';
});

// TEMPORARY - Hapus setelah selesai test
Route::get('/create-notif-for-user1', function() {
    try {
        DB::table('notifications')->insert([
            'user_id' => 1,
            'sender_id' => 1,
            'title' => 'Test Notifikasi',
            'message' => 'Ini notifikasi untuk Admin2',
            'type' => 'info',
            'is_read' => 0,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        return response()->json(['message' => 'Notifikasi dibuat untuk user_id=1']);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});