<?php

namespace App\Http\Controllers\Museum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Museum;
use Illuminate\Support\Facades\Log;

class MuseumController extends Controller
{
    public function index(Request $request){
        try{
            $museums = Museum::all();
            return response()->json([
                'status' => 'success',
                'museums' => $museums,
            ], 200);
        } catch (\Exception $e){
            Log::error('Museum error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get museums',
            ], 500);
        } catch (\Exception $e){
            Log::error('Museum error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Museum not found',
            ], 404);
        }
    }

    public function show($name){
        try{
            $museum = Museum::where('name', $name)->first();
            return response()->json([
                'status' => 'success',
                'museum' => $museum,
            ], 200);
        } catch (\Exception $e){
            Log::error('Museum error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get museum',
            ], 500);
        } catch (\Exception $e){
            Log::error('Museum error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Museum not found',
            ], 404);
        }
    }

    public function store(Request $request){
        try{
            $museum = Museum::create([
                'name' => $request->name,
                'location' => $request->location,
                'description' => $request->description,
                'photo' => $request->photo,
                'contact' => $request->contact,
                'email' => $request->email,
                'website' => $request->website,
                'open_time' => $request->open_time,
                'close_time' => $request->close_time,
            ]);
            return response()->json([
                'status' => 'success',
                'museum' => $museum,
            ], 200);
        } catch (\Exception $e){
            Log::error('Museum error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create museum',
            ], 500);
        }
    }

    public function update(Request $request, $id){
        try{
            $museum = Museum::find($id);
            $museum->update([
                'name' => $request->name,
                'location' => $request->location,
                'description' => $request->description,
                'photo' => $request->photo,
                'contact' => $request->contact,
                'email' => $request->email,
                'website' => $request->website,
                'open_time' => $request->open_time,
                'close_time' => $request->close_time,
            ]);
            return response()->json([
                'status' => 'success',
                'museum' => $museum,
            ], 200);
        } catch (\Exception $e){
            Log::error('Museum error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update museum',
            ], 500);
        }
    }

    public function destroy($id){
        try{
            $museum = Museum::find($id);
            $museum->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Museum deleted',
            ], 200);
        } catch (\Exception $e){
            Log::error('Museum error: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete museum',
            ], 500);
        }
    }
}
