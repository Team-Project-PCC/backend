<?php

namespace App\Http\Controllers\Museum;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Museum;

class MuseumContact extends Controller
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
            return response()->json([
                'museum'=>$museum,
                'contact_info'=>$museum->contact_info,
            ],
                 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get museum',
            ], 500);
        }
    }

    public function store(Request $request){
        try{
            $museum = Museum::where('name', $request->name)->first();
            if(!$museum){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Museum not found',
                ], 404);
            }
            $museum->contact_info()->create([
                'email' => $request->email,
                'phone' => $request->phone,
                'website' => $request->website,
                'facebook' => $request->facebook,
                'instagram' => $request->instagram,
                'twitter' => $request->twitter,
                'youtube' => $request->youtube,
                'linkedin' => $request->linkedin,
                'tiktok' => $request->tiktok,
                'whatsapp' => $request->whatsapp,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Contact info added successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add contact info',
            ], 500);
        }
    }

    public function update(Request $request){
        try{
            $museum = Museum::where('name', $request->name)->first();
            if(!$museum){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Museum not found',
                ], 404);
            }
            $museum->contact_info()->update([
                'email' => $request->email,
                'phone' => $request->phone,
                'website' => $request->website,
                'facebook' => $request->facebook,
                'instagram' => $request->instagram,
                'twitter' => $request->twitter,
                'youtube' => $request->youtube,
                'linkedin' => $request->linkedin,
                'tiktok' => $request->tiktok,
                'whatsapp' => $request->whatsapp,
            ]);
            return response()->json([
                'status' => 'success',
                'message' => 'Contact info updated successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update contact info',
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
            $museum->contact_info()->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Contact info deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete contact info',
            ], 500);
        }
    }
}
