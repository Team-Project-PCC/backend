<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GoogleController extends Controller
{
    // Method Sign Up
    public function signUpWithGoogle(Request $request)
    {
        try {
            $token = $request->input('id_token');
            $client = new Google_Client(['client_id' => config('services.google.client_id')]);
            $payload = $client->verifyIdToken($token);

            if (!$payload) {
                return response()->json(['error' => 'Invalid Google token'], 401);
            }

            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'];

            $user = User::where('email', $email)->first();

            if ($user) {
                return response()->json(['error' => 'User already exists. Please log in instead.'], 409);
            }

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'google_id' => $googleId,
                'password' => Hash::make(Str::random(16)), 
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong.'], 500);
        }
    }


    // Method Login
    public function loginWithGoogle(Request $request)
    {
        $token = $request->input('id_token'); // Token dari aplikasi Android
        $client = new Google_Client(['client_id' => config('services.google.client_id')]);
        $payload = $client->verifyIdToken($token);

        if ($payload) {
            $email = $payload['email'];
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found. Please sign up.'], 404);
            }

            // Generate token API atau JWT
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
            ], 200);
        } else {
            return response()->json(['error' => 'Invalid Google token'], 401);
        }
    }
}
