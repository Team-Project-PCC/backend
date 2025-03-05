<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PromotionRules;
use App\Models\Promotion;
use Illuminate\Support\Facades\Validator;
use Exception;

class PromotionRuleController extends Controller
{
    public function index(){
        try{
            $promotionRules = PromotionRules::all();
            return response()->json($promotionRules, 200);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotion rules',
                'error' => $e
            ], 500);
        }
    }

    public function show($rule_id){
        try{
            $promotionRules = PromotionRules::find($rule_id);
            return response()->json($promotionRules, 200);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotion rules',
                'error' => $e
            ], 500);
        }
    }
    
    public function store(Request $request, $promotion_id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'prmotion_id'=> 'required|integer',
                'rules' => 'required|array|min:1',
                'rules.*.type' => 'required|in:percentage,max_discount,min_order',
                'rules.*.value' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input',
                    'errors' => $validator->errors()
                ], 400);
            }

            $promotion = Promotion::find($promotion_id);
            if (!$promotion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Promotion not found'
                ], 404);
            }

            // Tambahkan aturan baru ke promosi
            foreach ($request->rules as $rule) {
                $promotion->promotion_rules()->create([
                    'type' => $rule['type'],
                    'value' => $rule['value']
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion rules added successfully'
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to add promotion rules',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Memperbarui aturan promosi tertentu
    public function update(Request $request, $rule_id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required|in:percentage,flat',
                'value' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input',
                    'errors' => $validator->errors()
                ], 400);
            }

            $rule = PromotionRules::find($rule_id);
            if (!$rule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Promotion rule not found'
                ], 404);
            }

            $rule->update([
                'type' => $request->type,
                'value' => $request->value
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion rule updated successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update promotion rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Menghapus aturan promosi tertentu
    public function destroy($rule_id)
    {
        try {
            $rule = PromotionRules::find($rule_id);
            if (!$rule) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Promotion rule not found'
                ], 404);
            }

            $rule->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion rule deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete promotion rule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
