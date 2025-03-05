<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Mockery\Expectation;
use Exception;
use App\Models\Promotion;
use Illuminate\Support\Facades\Validator;
use App\Models\PromotionTypes;

class PromotionController extends Controller
{
    public function index (){
        try{
            $promotion = Promotion::all();
            $promotion->load(['promotion_type','promotion_rules', 'promotion_usages']);
            return response()->json($promotion, 200);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotion',
                'error' => $e
            ], 500);
        }
    }

    public function show($id){
        try{
            $promotion = Promotion::find($id);
            $promotion->load(['promotion_type','promotion_rules', 'promotion_usages']);
            return response()->json($promotion, 200);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotion',
                'error' => $e
            ], 500);
        }
    }

    public function store(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'event_id' => 'required|integer',
                'code' => 'required|string',
                'status' => 'required|in:active,inactive',
                'start_date' => 'required|date',
                'end_date' => 'required|date',
                'usage_limit' => 'required|integer',
                'type_id' => 'required|integer',
            ]);
    
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input',
                    'error' => $validator->errors()
                ], 400);
            }
    
            $promotion = Promotion::create($request->all());
    
            return response()->json([
                'status' => 'success',
                'message' => 'Promotion created successfully',
                'data' => $promotion
            ], 201);
    
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    

    public function update(Request $request){
        try{
            $validator = Validator::make($request->all(), [
                'event_id' => 'nullable|integer',
                'code' => 'nullable|string',
                'status' => 'nullable|in:active,inactive',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'usage_limit' => 'nullable|integer',
                'type_id' => 'nullable|integer',
            ]);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update promotion',
                'error' => $e
            ], 500);
        }
    }
}
