<?php

namespace App\Http\Controllers\Promotion;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Exception;
use App\Models\Promotion;
use App\Models\PromotionRules;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    public function index()
    {
        try {
            $promotions = Promotion::with(['promotion_rules', 'promotion_events'])->get();
            return response()->json(['status' => 'success', 'data' => $promotions], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch promotions', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $promotion = Promotion::with(['promotion_rules', 'promotion_events'])->findOrFail($id);
            return response()->json(['status' => 'success', 'data' => $promotion], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Promotion not found', 'error' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'sometimes|string|unique:promotions,code,' . $id,
                'type' => 'sometimes|in:fixed_discount,percentage',
                'value' => 'sometimes|numeric|min:0',
                'valid_from' => 'sometimes|date',
                'valid_until' => 'sometimes|date|after:valid_from',
                'max_usage' => 'sometimes|integer|min:1',
                'is_active' => 'sometimes|boolean',
                'event_id' => 'nullable|array',
                'event_id.*' => 'exists:events,id',
                'rules' => 'nullable|array',
                'rules.*.rule_id' => 'sometimes|exists:promotion_rules,id',
                'rules.*.rule_type' => 'required|in:max_discount,min_order',
                'rules.*.rule_value' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Invalid input', 'errors' => $validator->errors()], 400);
            }

            $promotion = Promotion::findOrFail($id);
            $promotion->update($request->only(['code', 'type', 'value', 'valid_from', 'valid_until', 'max_usage', 'is_active']));

            if ($request->has('rules')) {
                foreach ($request->rules as $rule) {
                    PromotionRules::updateOrCreate(
                        ['id' => $rule['rule_id'] ?? null, 'promotion_id' => $promotion->id],
                        ['rule_type' => $rule['rule_type'], 'rule_value' => $rule['rule_value']]
                    );
                }
            }

            if ($request->has('event_id')) {
                $promotion->promotion_events()->delete();
                foreach ($request->event_id as $event_id) {
                    $promotion->promotion_events()->create(['event_id' => $event_id]);
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Promotion updated successfully', 'data' => $promotion->load(['promotion_rules', 'promotion_events'])], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to update promotion', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $promotion = Promotion::findOrFail($id);
            $promotion->promotion_rules()->delete();
            $promotion->promotion_events()->delete();
            $promotion->delete();

            return response()->json(['status' => 'success', 'message' => 'Promotion deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete promotion', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|unique:promotions,code',
                'type' => 'required|in:fixed_discount,percentage',
                'value' => 'required|numeric|min:0',
                'valid_from' => 'required|date',
                'valid_until' => 'required|date|after:valid_from',
                'max_usage' => 'required|integer|min:1',
                'is_active' => 'boolean',
                'event_id' => 'nullable|exists:events,id',
                'rules' => 'nullable|array',
                'rules.*.rule_type' => 'required|in:max_discount,min_order',
                'rules.*.rule_value' => 'required|numeric|min:0',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid input',
                    'errors' => $validator->errors()
                ], 400);
            }

            $ruleTypes = collect($request->rules)->pluck('rule_type');
            if ($request->type == 'fixed_discount' && $ruleTypes->contains('max_discount')) {
                return response()->json(['status' => 'error', 'message' => "'fixed_discount' cannot be used with 'max_discount'."], 400);
            }
            if ($request->type == 'percentage' && !$ruleTypes->contains('max_discount')) {
                return response()->json(['status' => 'error', 'message' => "'percentage' must always be accompanied by 'max_discount'."], 400);
            }

            $promotion = Promotion::create($request->only([
                'code', 'type', 'value', 'valid_from', 'valid_until', 'max_usage', 'is_active', 'event_id'
            ]));

            if ($request->has('rules')) {
                foreach ($request->rules as $rule) {
                    PromotionRules::create([
                        'promotion_id' => $promotion->id,
                        'rule_type' => $rule['rule_type'],
                        'rule_value' => $rule['rule_value']
                    ]);
                }
            }

            if($request->has('event_id')){
                foreach ($request->event_id as $event_id) {
                    $promotion->promotion_events()->create([
                        'event_id' => $event_id,
                        'promotion_id' => $promotion->id
                    ]);
                }
            }

            return response()->json(['status' => 'success', 'message' => 'Promotion created successfully', 'data' => $promotion->load(['promotion_rules', 'promotion_events'])], 201);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to create promotion', 'error' => $e->getMessage()], 500);
        }
    }
}
