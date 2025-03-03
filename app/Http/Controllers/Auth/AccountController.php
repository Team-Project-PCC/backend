<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;

class AccountController extends Controller
{
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
                $image = file_get_contents($file->getRealPath());
                $encodedImage = base64_encode($image);

                $response = Http::asForm()->post('https://api.imgbb.com/1/upload', [
                    'key' => env('IMGBB_API_KEY'),
                    'image' => $encodedImage
                ]);

                $result = $response->json();

                if (isset($result['data']['url'])) {
                    $dataToUpdate['url_avatar'] = $result['data']['url'];
                }
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
