<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TicketOrder;
use App\Models\Ticket;
use App\Models\Promotion;
use App\Models\User;
use App\Models\TicketOrderDetails;
use App\Models\TicketCategory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Exception;

class TicketController extends Controller
{
    public function index()
    {
        try {
            $ticketOrders = TicketOrder::with('ticket', 'ticket.category', 'promotion')->get();
            return response()->json([
                'status' => 'success',
                'data' => $ticketOrders
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch ticket orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $ticketOrder = TicketOrder::with('ticket', 'ticket.category', 'promotion')->findOrFail($id);
            return response()->json([
                'status' => 'success',
                'data' => $ticketOrder
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket order not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch ticket order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            $ticketOrder = TicketOrder::findOrFail($id);
            $ticketCategory = TicketCategory::findOrFail($ticketOrder->ticket->ticket_category_id);
            
            $totalPrice = $request->quantity * $ticketCategory->price;

            $ticketOrder->update([
                'total_quantity' => $request->quantity,
                'total_price' => $totalPrice,
            ]);

            $ticketOrder->details()->update([
                'quantity' => $request->quantity,
                'subtotal' => $totalPrice,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ticket order updated successfully',
                'data' => $ticketOrder
            ], 200);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update ticket order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $ticketOrder = TicketOrder::findOrFail($id);
            $ticketOrder->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Ticket order deleted successfully'
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ticket order not found'
            ], 404);
        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete ticket order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'event_id' => 'required|integer',
                'ticket_category_id' => 'required|integer',
                'quantity' => 'required|integer|min:1',
                'code_promotion' => 'nullable|string',
            ]);


            $user = User::findOrFail($request->user()->id);
            $ticketCategory = TicketCategory::findOrFail($request->ticket_category_id);
            $totalPrice = $request->quantity * $ticketCategory->price;
            $promotion = null;
            $discount = 0;

            $ticket = Ticket::create([
                'event_id' => $request->event_id,
                'ticket_category_id' => $request->ticket_category_id,
                'user_id' => $user->id,
            ]);

            if ($request->code_promotion) {
                $promotion = Promotion::where('code', $request->code_promotion)->first();

                if ($promotion) {
                    $eventPromotion = DB::table('event_promotions')
                        ->where('event_id', $request->event_id)
                        ->where('promotion_id', $promotion->id) // Gunakan ID, bukan kode
                        ->first();

                    if (!$eventPromotion) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Promotion is not valid for this event',
                        ], 400);
                    }

                    // Validasi tanggal promosi
                    if (now()->lt($promotion->valid_from) || now()->gt($promotion->valid_until)) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Promotion is expired or not yet active',
                        ], 400);
                    }

                    // Hitung diskon
                    if ($promotion->type == 'percentage') {
                        $discount = ($totalPrice * $promotion->value) / 100;
                        
                        if ($promotion->max_discount && $discount > $promotion->max_discount) {
                            $discount = $promotion->max_discount;
                        }
                        
                        if ($promotion->min_discount && $discount < $promotion->min_discount) {
                            $discount = $promotion->min_discount;
                        }

                    } else {
                        $discount = $promotion->value;
                    }

                    $totalPrice = max(0, $totalPrice - $discount);
                }
            }

            $ticketOrder = TicketOrder::create([
                'ticket_id' => $ticket->id,
                'total_quantity' => $request->quantity,
                'total_price' => $totalPrice,
                'promotion_id' => $promotion ? $promotion->id : null, // Simpan ID, bukan kode
            ]);

            TicketOrderDetails::create([
                'ticket_order_id' => $ticketOrder->id,
                'quantity' => $request->quantity,
                'price' => $ticketCategory->price,
                'subtotal' => $totalPrice,
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Ticket ordered successfully',
                'data' => $ticketOrder
            ], 201);
        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found'
            ], 404);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to order ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
