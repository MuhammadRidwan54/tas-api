<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class NotificationController extends Controller
{
    public function saveFcmToken(Request $request)
    {
        // Manual auth
        $token = $request->bearerToken();
        if (!$token) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $token))
            ->first();
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = User::find($accessToken->tokenable_id);
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 401);
        }
        
        Auth::setUser($user);
        
        // Kode asli
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        $user = Auth::user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json(['message' => 'FCM token saved']);
    }

    public function getNotifications(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        // Debug: cek semua token yang ada
        $allTokens = DB::table('personal_access_tokens')->get();
        
        // Coba berbagai metode hash
        $hashedToken1 = hash('sha256', $token);
        $hashedToken2 = $token; // langsung tanpa hash
        
        $accessToken = DB::table('personal_access_tokens')
            ->where('token', $hashedToken1)
            ->orWhere('token', $hashedToken2)
            ->first();
        
        return response()->json([
            'received_token' => $token,
            'hashed_sha256' => $hashedToken1,
            'token_length' => strlen($token),
            'all_tokens_count' => $allTokens->count(),
            'all_tokens' => $allTokens->map(function($t) {
                return [
                    'id' => $t->id,
                    'token_preview' => substr($t->token, 0, 20) . '...',
                    'tokenable_id' => $t->tokenable_id
                ];
            }),
            'found_token' => $accessToken ? true : false
        ]);
    }

    public function markAsRead(Request $request, $id)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $token))
            ->first();
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = User::find($accessToken->tokenable_id);
        
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 401);
        }
        
        Auth::setUser($user);
        
        $notification = Notification::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $notification->update([
            'is_read' => true,
            'read_at' => now()
        ]);

        return response()->json(['message' => 'Marked as read']);
    }

    public function markAllAsRead(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $token))
            ->first();
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = User::find($accessToken->tokenable_id);
        
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 401);
        }
        
        Auth::setUser($user);
        
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json(['message' => 'All notifications marked as read']);
    }

    public function unreadCount(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = DB::table('personal_access_tokens')
            ->where('token', hash('sha256', $token))
            ->first();
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = User::find($accessToken->tokenable_id);
        
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 401);
        }
        
        Auth::setUser($user);
        
        $count = Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $count]);
    }

    public static function sendPushNotification($userId, $title, $message, $type = 'info', $tasId = null)
    {
        $user = User::find($userId);
        
        if (!$user || !$user->fcm_token) {
            return false;
        }

        $notification = Notification::create([
            'user_id' => $userId,
            'sender_id' => Auth::id(),
            'tas_id' => $tasId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false
        ]);

        try {
            $credentials = config('firebase.credentials');
            
            if (is_string($credentials)) {
                $credentials = json_decode($credentials, true);
            }
            
            $factory = (new Factory)->withServiceAccount($credentials);
            $messaging = $factory->createMessaging();

            $firebaseNotification = FirebaseNotification::create($title, $message);
            
            $cloudMessage = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($firebaseNotification)
                ->withData([
                    'notification_id' => (string) $notification->id,
                    'type' => $type,
                    'tas_id' => (string) $tasId
                ]);

            $messaging->send($cloudMessage);
        } catch (\Exception $e) {
            \Log::error('FCM Error: ' . $e->getMessage());
        }

        return $notification;
    }
}