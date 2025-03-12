<?php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class RegisterController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            // Membuat user
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal membuat akun, coba lagi.'
                ], 500);
            }

            $user->assignRole('user');

            $user->sendEmailVerificationNotification();

            return response()->json([
                'success' => true,
                'message' => 'Registrasi berhasil. Silakan periksa email untuk verifikasi.',
            ], 201);
        } catch (\Exception $e) {
            Log::error('Register Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan, silakan coba lagi nanti.'
            ], 500);
        }
    }
}
