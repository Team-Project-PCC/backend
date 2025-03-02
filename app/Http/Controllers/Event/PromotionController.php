<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotions;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    public function index()
    {
        try{
            $promotions = Promotions::with('event_promotions.event')->get();
            return response()->json([
                'status' => 'success',
                'data' => $promotions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotions'
            ], 500);
        }
    }

    public function show($id)
    {
        try{
            $promotion = Promotions::with('event_promotions.event')->find($id);
            if(!$promotion){
                return response()->json([
                    'status' => 'error',
                    'message' => 'Promotion not found'
                ], 404);
            }
            return response()->json([
                'status' => 'success',
                'data' => $promotion
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotion'
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code'=>'required|string|unique:promotions',
                'type'=>'required|in:percentage, nominal',
                'value'=>'required|numeric',
                'max_discount'=>'required|numeric',
                'min_order'=>'required|numeric',
                'valid_from'=>'required|date',
                'valid_until'=>'required|date',
                'usage_limit'=>'required|numeric',
                'usage_count'=>'required|numeric',
                'event_id'=>'nullable|exists:events,id'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $promotion = Promotions::create([
                'code' => $request->code,
                'type' => $request->type,
                'value' => $request->value,
                'max_discount' => $request->max_discount,
                'min_order' => $request->min_order,
                'valid_from' => $request->valid_from,
                'valid_until' => $request->valid_until,
                'usage_limit' => $request->usage_limit,
                'usage_count' => $request->usage_count,
                'is_active' => true
            ]);

            if($request->event_id){
                $promotion->event()->attach($request->event_id);

                $event_promotion = $promotion->event_promotions()->where(
                    ['event_id' => $request->event_id,
                    'promotion_id' => $promotion->id])->first();
            }

            $promotion->event_promotions()->save($event_promotion);

            return response()->json([
                'status' => 'success',
                'data' => $promotion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create promotion'
            ], 500);
        }
    }

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'nullable|string|unique:promotions,code,' . $request->id,
                'type' => 'nullable|in:percentage,fixed',
                'value' => 'nullable|numeric',
                'max_discount' => 'nullable|numeric',
                'min_order' => 'nullable|numeric',
                'valid_from' => 'nullable|date',
                'valid_until' => 'nullable|date',
                'usage_limit' => 'nullable|numeric',
                'usage_count' => 'nullable|numeric',
                'event_id' => 'nullable|array',
                'event_id.*' => 'exists:events,id'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $promotion = Promotions::find($request->id);
            if (!$promotion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Promotion not found'
                ], 404);
            }

            $dataToUpdate = collect($request->only([
                'code', 'type', 'value', 'max_discount', 'min_order',
                'valid_from', 'valid_until', 'usage_limit', 'usage_count'
            ]))->filter(fn($value) => filled($value))->toArray();

            if (!empty($dataToUpdate)) {
                $promotion->update($dataToUpdate);
            }

            if ($request->has('event_id')) {
                $promotion->events()->sync($request->event_id);
            }

            return response()->json([
                'status' => 'success',
                'data' => $promotion
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $promotion = Promotions::with('event_promotions')->find($id);
            if (!$promotion) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Promotion not found'
                ], 404);
            }

            $promotion->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Promotion deleted'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete promotion'
            ], 500);
        }
    }

}
