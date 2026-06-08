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
use Laravel\Sanctum\PersonalAccessToken;

class NotificationController extends Controller
{
    public function saveFcmToken(Request $request)
    {
        $tokenString = $request->bearerToken();
        
        if (!$tokenString) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = PersonalAccessToken::findToken($tokenString);
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = $accessToken->tokenable;
        
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 401);
        }
        
        Auth::setUser($user);
        
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
        $tokenString = $request->bearerToken();
        
        if (!$tokenString) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = PersonalAccessToken::findToken($tokenString);
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = $accessToken->tokenable;
        
        if (!$user) {
            return response()->json(['error' => 'User tidak ditemukan'], 401);
        }
        
        Auth::setUser($user);
        
        $notifications = Notification::where('user_id', $user->id)
            ->with('sender', 'tas')
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notifications);
    }

    public function markAsRead(Request $request, $id)
    {
        $tokenString = $request->bearerToken();
        
        if (!$tokenString) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = PersonalAccessToken::findToken($tokenString);
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = $accessToken->tokenable;
        
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
        $tokenString = $request->bearerToken();
        
        if (!$tokenString) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = PersonalAccessToken::findToken($tokenString);
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = $accessToken->tokenable;
        
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
        $tokenString = $request->bearerToken();
        
        if (!$tokenString) {
            return response()->json(['error' => 'Token tidak ditemukan'], 401);
        }
        
        $accessToken = PersonalAccessToken::findToken($tokenString);
        
        if (!$accessToken) {
            return response()->json(['error' => 'Token tidak valid'], 401);
        }
        
        $user = $accessToken->tokenable;
        
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