<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class LogoutController extends Controller
{
    public function store(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());

            return response()->json([
                'status' => 'success',
                'message' => 'Token revoked',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to logout',
            ], 500);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token expired',
            ], 401);
        } catch (TokenExpiredException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token invalid',
            ], 400);
        }
    }
}
