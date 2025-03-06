<?php

namespace App\Http\Controllers\Order;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TicketOrder;
use App\Models\Promotion;
use App\Models\PromotionRules;
use App\Models\TicketOrderDetails;
use App\Models\TicketCategory;
use Exception;

class TicketController extends Controller
{
    public function store(Request $request)
    {
        try{
            // Validasi request
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'event_id' => 'required|exists:events,id',
                'tickets' => 'required|array',
                'tickets.*.ticket_category_id' => 'required|exists:ticket_categories,id',
                'tickets.*.quantity' => 'required|integer|min:1',
                'promotion_code' => 'nullable|string|exists:promotions,code',
            ]);

            $totalQuantity = 0;
            $totalPrice = 0;
            $ticketDetails = [];

            // Hitung harga total dan detail tiket
            foreach ($request->tickets as $ticket) {
                $ticketCategory = TicketCategory::findOrFail($ticket['ticket_category_id']);
                $subtotal = $ticket['quantity'] * $ticketCategory->price;

                $ticketDetails[] = [
                    'ticket_category_id' => $ticketCategory->id,
                    'quantity' => $ticket['quantity'],
                    'price' => $ticketCategory->price,
                    'subtotal' => $subtotal,
                ];

                $totalQuantity += $ticket['quantity'];
                $totalPrice += $subtotal;
            }

            // Cek promo
            $promotion = null;
            $discount = 0;
            if ($request->filled('promotion_code')) {
                $promotion = Promotion::where('code', $request->promotion_code)
                    ->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now())
                    ->first();

                if ($promotion) {
                    $promotionRules = PromotionRules::where('promotion_id', $promotion->id)->get();

                    // Cek aturan promo
                    $isValidPromo = true;
                    $maxDiscount = null;
                    foreach ($promotionRules as $rule) {
                        if ($rule->rule_type === 'min_order' && $totalPrice < $rule->rule_value) {
                            $isValidPromo = false;
                            break;
                        }
                        if ($rule->rule_type === 'max_discount') {
                            $maxDiscount = $rule->rule_value;
                        }
                    }

                    if ($isValidPromo) {
                        if ($promotion->type === 'fixed_discount') {
                            $discount = $promotion->value;
                        } elseif ($promotion->type === 'percentage') {
                            $discount = ($promotion->value / 100) * $totalPrice;
                        }

                        // Batasi diskon jika ada max_discount
                        if ($maxDiscount !== null) {
                            $discount = min($discount, $maxDiscount);
                        }

                        $totalPrice = max(0, $totalPrice - $discount);
                    } else {
                        $promotion = null; // Batalkan promo jika aturan tidak terpenuhi
                    }
                }
            }

            // Simpan order utama
            $ticketOrder = TicketOrder::create([
                'user_id' => $request->user_id,
                'event_id' => $request->event_id,
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'promotion_id' => $promotion ? $promotion->id : null,
            ]);

            // Simpan detail tiket
            foreach ($ticketDetails as $detail) {
                TicketOrderDetails::create([
                    'ticket_order_id' => $ticketOrder->id,
                    'ticket_category_id' => $detail['ticket_category_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'subtotal' => $detail['subtotal'],
                ]);
            }

            return response()->json([
                'message' => $promotion ? 'Ticket order created with promotion applied!' : 'Ticket order created successfully!',
                'order' => $ticketOrder,
                'details' => $ticketOrder->ticketOrderDetails,
                'discount_applied' => $discount,
            ], 201);
        } catch(Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create ticket order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
