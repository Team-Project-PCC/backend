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

class TicketController extends Controller
{
    public function store(Request $request)
    {
        try {
            DB::beginTransaction(); // Memulai transaksi

            $validated = $request->validate([
                'event_id' => 'required|integer',
                'ticket_category_id' => 'required|integer',
                'quantity' => 'required|integer',
                'code_promotion' => 'nullable|integer',
            ]);

            $user = User::findOrFail($request->user()->id);
            $ticketCategory = TicketCategory::findOrFail($request->ticket_category_id);
            
            $ticket = Ticket::create([
                'event_id' => $request->event_id,
                'ticket_category_id' => $request->ticket_category_id,
                'user_id' => $user->id,
            ]);

            if ($request->code_promotion) {
                $promotion = Promotion::where('id', $request->code_promotion)->first();
                $eventPromotion = DB::table('event_promotions')
                    ->where('event_id', $request->event_id)
                    ->where('promotion_id', $request->code_promotion)
                    ->first();

                if (!$eventPromotion) {
                    DB::rollBack(); // Batalkan transaksi jika promosi tidak valid
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Promotion is not valid for this event',
                    ], 400);
                }
            }

            $totalPrice = $request->quantity * $ticketCategory->price;

            $ticketOrder = TicketOrder::create([
                'ticket_id' => $ticket->id,
                'total_quantity' => $request->quantity,
                'total_price' => $totalPrice,
                'promotion_id' => $request->code_promotion,
            ]);

            $ticketOrderDetails = TicketOrderDetails::create([
                'ticket_order_id' => $ticketOrder->id,
                'quantity' => $request->quantity,
                'price' => $ticketCategory->price,
                'subtotal' => $totalPrice,
            ]);

            DB::commit(); // Simpan transaksi ke database

            return response()->json([
                'status' => 'success',
                'message' => 'Ticket ordered successfully',
                'data' => $ticketOrder
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Batalkan transaksi jika terjadi kesalahan
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to order ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
