<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;



class EmailVerificationController extends Controller
{
    public function verify(Request $request, $id, $hash)
{
    Log::info("Memulai verifikasi email untuk user ID: $id");

    $user = User::findOrFail($id);
    
    if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        Log::error("Hash tidak cocok untuk user ID: $id");
        return response()->json(['message' => 'Invalid verification link'], 400);
    }

    if ($user->hasVerifiedEmail()) {
        Log::info("Email sudah diverifikasi sebelumnya untuk user ID: $id");
        return response()->json(['message' => 'Email already verified'], 200);
    }
    
    $user->markEmailAsVerified();
    $user->email_verified_at = now();
    $user->save();
    Log::info("Email berhasil diverifikasi untuk user ID: $id");

    if (config('auth.defaults.guard') === 'sanctum') {
        Auth::loginUsingId($user->id);
        $token = $user->createToken('AuthToken')->plainTextToken;
        Log::info("Token Sanctum berhasil dibuat untuk user ID: $id");
    } 
    else {
        try {
            if (!$token = JWTAuth::fromUser($user)) {
                Log::error("Gagal membuat token JWT untuk user ID: $id");
                return response()->json(['message' => 'Could not create token'], 500);
            }
            Log::info("Token JWT berhasil dibuat untuk user ID: $id");
        } catch (\Exception $e) {
            Log::error("Exception saat membuat token JWT: " . $e->getMessage());
            return response()->json(['message' => 'Failed to generate token'], 500);
        }
    }

    Log::info("Redirect ke frontend dengan token untuk user ID: $id");

    return redirect()->away(env('FRONTEND_URL') . "/email-verifikasi?token=$token&id=$id");
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
