<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;


class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);
    
        // Periksa apakah hash cocok dengan email pengguna
        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Invalid verification link'], 400);
        }
    
        // Jika email sudah terverifikasi sebelumnya
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 200);
        }
    
        // Tandai email sebagai terverifikasi
        $user->markEmailAsVerified();
        $user->email_verified_at = now();
        $user->save();
    
        // **Menggunakan Laravel Sanctum**
        if (config('auth.defaults.guard') === 'sanctum') {
            Auth::login($user->first());
            $token = $user->createToken('AuthToken')->plainTextToken;
        } 
        // **Menggunakan JWT**
        else {
            try {
                if (!$token = JWTAuth::fromUser($user)) {
                    return response()->json(['message' => 'Could not create token'], 500);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to generate token'], 500);
            }
        }
    
        return response()->json([
            'message' => 'Email verified successfully',
            'token' => $token,
            'user' => $user
        ], 200);
    }

    /**
     * Resend the email verification notification.
     */
    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified'], 400);
        }

        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message' => 'Verification link sent']);
    }
}
