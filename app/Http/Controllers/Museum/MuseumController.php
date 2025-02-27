<?php

namespace App\Http\Controllers\Museum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Museum;
use Illuminate\Support\Facades\Validator;

class MuseumController extends Controller
{
    public function index(){
        try{
            $museums = Museum::all();
            return response()->json($museums, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get museums',
            ], 500);
        }
    }

    public function show($name){
        try{
            $museum = Museum::where('name', $name)->first();
            if(!$museum){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Museum not found',
                ], 404);
            }
            return response()->json($museum, 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get museum',
            ], 500);
        }
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'location' => 'required|string',
                'email' => 'nullable|string|email|max:255',
                'phone' => 'nullable|string',
                'website' => 'nullable|string',
            ]);

            if($validator->fails()){
                return response()->json($validator->errors(), 422);
            }

            $museum = Museum::create([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'email' => $request->email,
                'phone' => $request->phone,
                'website' => $request->website,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Museum created',
                'data' => $museum,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create museum',
            ], 500);
        }
    }

    public function update(Request $request, $name){
        try{
            $museum = Museum::where('name', $name)->first();
            if(!$museum){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Museum not found',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'location' => 'required|string',
                'email' => 'nullable|string|email|max:255',
                'phone' => 'nullable|string',
                'website' => 'nullable|string',
            ]);

            if($validator->fails()){
                return response()->json($validator->errors(), 422);
            }

            $museum = Museum::where('name', $name)->update([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'email' => $request->email,
                'phone' => $request->phone,
                'website' => $request->website,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Museum updated',
                'data' => $museum,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update museum',
            ], 500);
        }
    }

    public function destroy($name){
        try{
            $museum = Museum::where('name', $name)->first();
            if(!$museum){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Museum not found',
                ], 404);
            }

            $museum->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Museum deleted',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete museum',
            ], 500);
        }
    }
}
