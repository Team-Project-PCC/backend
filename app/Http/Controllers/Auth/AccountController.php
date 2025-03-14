<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AccountController extends Controller
{
    public function profile()
    {
        $user = User::find(Auth::id());
        $history = $user->ticketOrders()->with(
            'ticketOrderDetails.ticketCategory',
            'payment'
        )->get();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user,
            'history' => $history
        ], 200);
    }
    
    public function update_profile(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string',
                'email' => 'nullable|email|unique:users,email,' . Auth::id(),
                'password' => 'nullable|string|min:8',
                'image' => 'nullable|file|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $user = User::find(Auth::id());

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found'
                ], 404);
            }

            $dataToUpdate = collect($request->only(['name', 'email', 'password']))->filter()->toArray();

            if (array_key_exists('password', $dataToUpdate)) {
                $dataToUpdate['password'] = bcrypt($dataToUpdate['password']);
            }

            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = 'avatar_' . Auth::id() . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('avatars', $filename, 'public'); 
                $imageUrl = asset('storage/' . $path);
                $dataToUpdate['url_avatar'] = $imageUrl;
            }

            $user->update($dataToUpdate);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated',
                'data' => $user
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}