<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PromotionTypes;
use Exception;
use Illuminate\Support\Facades\Validator;

class PromotionTypeController extends Controller
{
    public function index()
    {
        try{
            $types = PromotionTypes::all();
            return response()->json($types, 200);
        }catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotion types',
                'error' => $e
            ], 500);
        }
    }

    public function show($id)
    {
        try{
            $type = PromotionTypes::find($id);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotion type',
                'error' => $e
            ], 500);
        }
    }

    public function store(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:promotion_types',
            ]);
    
            if (!$validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input',
                    'error' => $validator->errors()
                ], 400);
            }

            $type = PromotionTypes::create([
                'name' => $request->name
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $type
            ], 201);

        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input',
                'error' => $validator->errors()
            ]);
        }
    }

    public function update(Request $request, $id){
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|unique:promotion_types',
            ]);
    
            if (!$validator->fails()){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input',
                    'error' => $validator->errors()
                ], 400);
            }

            $type = PromotionTypes::find($id);
            $type->name = $request->name;
            $type->save();

            return response()->json([
                'status' => 'success',
                'data' => $type
            ], 200);

        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid input',
                'error' => $validator->errors()
            ]);
        }
    }

    public function destroy($id){
        try{
            $type = PromotionTypes::find($id);
            $type->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion type deleted'
            ], 200);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete promotion type',
                'error' => $e
            ], 500);
        }
    }
}
