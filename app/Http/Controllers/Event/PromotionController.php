<?php

namespace App\Http\Controllers\Event;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Promotion;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    public function index()
    {
        try{
            $promotions = Promotion::with('event_promotions.event')->get();
            return response()->json([
                'status' => 'success',
                'data' => $promotions
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get promotions',
                'error' => $e
            ], 500);
        }
    }

    public function show($id)
    {
        try{
            $promotion = Promotion::with('event_promotions.event')->find($id);
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
                'code' => 'required|string|unique:promotions',
                'type' => 'required|in:percentage,fixed',
                'value' => 'required|numeric',
                'max_discount' => 'nullable|numeric',
                'min_order' => 'nullable|numeric',
                'valid_from' => 'required|date_format:Y-m-d H:i:s|after:now',
                'valid_until' => 'required|date_format:Y-m-d H:i:s|after:valid_from',
                'usage_limit' => 'required|integer',
                'usage_count' => 'nullable|integer',
                'event_id' => 'nullable|exists:events,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $promotion = Promotion::create([
                'code' => $request->code,
                'type' => $request->type,
                'value' => $request->value,
                'max_discount' => $request->max_discount,
                'min_order' => $request->min_order,
                'valid_from' => $request->valid_from,
                'valid_until' => $request->valid_until,
                'usage_limit' => $request->usage_limit,
                'usage_count' => $request->usage_count ?? 0,
            ]);

            if ($request->event_id) {
                $promotion->events()->sync([$request->event_id]); 
            }

            $promotion = Promotion::with('events')->find($promotion->id);

            return response()->json([
                'status' => 'success',
                'data' => $promotion
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create promotion',
                'error' => $e->getMessage()
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

            $promotion = Promotion::find($request->id);
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
            $promotion = Promotion::with('event_promotions')->find($id);
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
