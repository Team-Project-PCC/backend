<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TicketOrder;
use App\Models\Ticket;
use App\Models\Promotion;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        try{
            $request->validate([
                'ticket_id' => 'required|exists:tickets,id',
                'quantity' => 'required|integer|min:1',
                'promotion_id' => 'nullable|exists:promotions,id',
            ]);
    
            DB::beginTransaction();
            try {
                $ticket = Ticket::findOrFail($request->ticket_id);
                $pricePerTicket = $ticket->category->price;
                $totalPrice = $pricePerTicket * $request->quantity;
    
                // Jika ada promo, hitung diskon
                if ($request->promotion_id) {
                    $promotion = Promotion::findOrFail($request->promotion_id);
                    if ($promotion->discount_type === 'percentage') {
                        $totalPrice -= ($totalPrice * $promotion->discount_value / 100);
                    } else {
                        $totalPrice -= $promotion->discount_value;
                    }
                }
    
                // Simpan order tiket
                $order = TicketOrder::create([
                    'ticket_id' => $request->ticket_id,
                    'quantity' => $request->quantity,
                    'total_price' => max($totalPrice, 0), // Harga tidak boleh negatif
                    'promotion_id' => $request->promotion_id,
                ]);
    
                DB::commit();
                return response()->json([
                    'status' => 'success',
                    'message' => 'Ticket ordered successfully',
                    'data' => $order
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to order ticket',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to order ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
