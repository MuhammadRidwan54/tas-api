<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        if (!Auth::attempt([
            'email' => $request->email,
            'password' => $request->password
        ])) {

            return response()->json([
                'message' => 'Login gagal'
            ], 401);
        }

        $user = User::where(
            'email',
            $request->email
        )->first();

        // Catat waktu login
        $user->update(['last_seen' => Carbon::now()]);

        $token = $user
            ->createToken('token')
            ->plainTextToken;

        return response()->json([

            'token' => $token,

            'user' => $user
        ]);
    }

    // Tambah method baru — update last_seen saat app aktif
    public function updateLastSeen(Request $request)
    {
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $user->update(['last_seen' => Carbon::now()]);
        }
        return response()->json(['message' => 'ok']);
    }

    public function getUsers()
    {
        $users = User::select('id', 'name', 'email', 'role', 'last_seen')->get();
        return response()->json($users);
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name'     => 'required',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,boss'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role
        ]);

        return response()->json([
            'message' => 'User berhasil dibuat',
            'user'    => $user
        ], 201);
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User berhasil dihapus']);
    }
}