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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Payment;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;
use Illuminate\Support\Facades\DB;
use App\Models\PromotionUsages;
use App\Models\Event;
use Carbon\Carbon;

class TicketController extends Controller
{
    protected $request;
    public function __construct(Request $request)
    {
        $this->request = $request;
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3ds');
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validasi request
            $request->validate([
                'event_id' => 'required|exists:events,id',
                'tickets' => 'required|array',
                'tickets.*.ticket_category_id' => 'required|exists:ticket_categories,id',
                'tickets.*.quantity' => 'required|integer|min:1',
                'promotion_code' => 'nullable|string|exists:promotions,code',
                'payment_method' => 'required|in:cashless,cash',
                'date' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) use ($request) {
                        $event = Event::find($request->event_id);
                        if (!$event) {
                            return $fail('Event not found.');
                        }

                        if ($event->type === 'special' && $event->date->format('Y-m-d') !== $value) {
                            return $fail('Invalid date for this special event.');
                        }

                        if ($event->type === 'recurring') {
                            $isValidRecurring = false;

                            if ($event->recurringSchedules()->where('recurring_type', 'daily')->exists()) {
                                $isValidRecurring = true;
                            }

                            if ($event->recurringSchedules()->where('recurring_type', 'weekly')->exists()) {
                                $weekday = Carbon::parse($value)->format('l');
                                $isValidRecurring = $event->weeklySchedules()->where('day_of_week', $weekday)->exists();
                            }

                            if ($event->recurringSchedules()->where('recurring_type', 'monthly')->exists()) {
                                $day = Carbon::parse($value)->day;
                                $isValidRecurring = $event->monthlySchedules()->where('day', $day)->exists();
                            }

                            if ($event->recurringSchedules()->where('recurring_type', 'yearly')->exists()) {
                                $month = Carbon::parse($value)->month;
                                $day = Carbon::parse($value)->day;
                                $isValidRecurring = $event->yearlySchedules()->where('month', $month)->where('day', $day)->exists();
                            }

                            if (!$isValidRecurring) {
                                return $fail('Invalid date for this recurring event.');
                            }
                        }
                    },
                ],

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
                Log::info('Promotion code: ' . $request->promotion_code);
                $promotion = Promotion::where('code', $request->promotion_code)
                    ->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now())
                    ->first();

                $user = Auth::user();

                if ($promotion) {
                    if ($promotion->usage_limit > 0) {
                        $usedCount = TicketOrder::where('user_id', $user->id)
                            ->where('promotion_id', $promotion->id)
                            ->count();
                        if ($usedCount >= $promotion->usage_limit) {
                            return response()->json(['message' => 'Promotion code has exceeded usage limit'], 400);
                        }
                    }

                    $promotionRules = PromotionRules::where('promotion_id', $promotion->id)->get();
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

                        // Batasi diskon dengan max_discount jika ada
                        if ($maxDiscount !== null) {
                            $discount = min($discount, $maxDiscount);
                        }

                        // Pastikan totalPrice tidak negatif
                        $totalPrice = max(0, round($totalPrice - $discount, 2));
                    } else {
                        $promotion = null; // Hapus promo jika aturan tidak terpenuhi
                        $discount = 0;
                    }
                }
            }

            $user = Auth::user();
            // Simpan order utama
            $ticketOrder = TicketOrder::create([
                'user_id' => $user->id,
                'event_id' => $request->event_id,
                'total_quantity' => $totalQuantity,
                'total_price' => $totalPrice,
                'promotion_id' => $promotion ? $promotion->id : null,
                'date' => $request->date,
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

            $totalPrice = round($totalPrice, 2);

            $ticketOrder->update([
                'total_price' => $totalPrice,
            ]);

            $ticketOrder = TicketOrder::with('ticketOrderDetails')->find($ticketOrder->id);

            $payment = Payment::create([
                'method' => $request->payment_method,
                'amount' => $totalPrice,
                'ticket_order_id' => $ticketOrder->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            if ($request->payment_method !== 'cash') {
                $payload = [
                    'transaction_details' => [
                        'order_id' => $ticketOrder->id,
                        'gross_amount' => $totalPrice,
                    ],
                    'customer_details' => [
                        'first_name' => $user->name,
                        'email' => $user->email,
                    ],
                    'item_details' => [
                        [
                            'id' => $ticketOrder->id,
                            'price' => $totalPrice,
                            'quantity' => $ticketOrder->total_quantity,
                            'name' => 'Ticket Order',
                        ],
                    ],
                ];

                $snapToken = Snap::getSnapToken($payload);
                $payment->update(['snap_token' => $snapToken]);
                $payment->save();
            }

            if ($promotion) {
                PromotionUsages::create([
                    'promotion_id' => $promotion->id,
                    'user_id' => $user->id,
                    'order_id' => $ticketOrder->id,
                ]);

                $promotion->increment('current_usage');
            }

            $ticketOrder = TicketOrder::with('ticketOrderDetails', 'payment')->find($ticketOrder->id);

            DB::commit();
            return response()->json([
                'message' => $promotion ? 'Ticket order created with promotion applied!' : 'Ticket order created successfully!',
                'order' => $ticketOrder,
                'discount_applied' => $discount,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create ticket order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showLatestOrder(){
        try{
            $user = Auth::user();
            $ticketOrder = TicketOrder::where('user_id', $user->id)->latest()->first();
            $ticketOrder = TicketOrder::with('ticketOrderDetails', 'payment')->find($ticketOrder->id);
            return response()->json($ticketOrder, 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Order not found', 'error' => $e->getMessage()], 404);
        }
    }

    public function index()
    {
        try {
            $orders = TicketOrder::with('ticketOrderDetails')->get();
            return response()->json($orders, 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to fetch orders', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $order = TicketOrder::with('ticketOrderDetails')->findOrFail($id);
            return response()->json($order, 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Order not found', 'error' => $e->getMessage()], 404);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'tickets' => 'required|array',
                'tickets.*.ticket_category_id' => 'required|exists:ticket_categories,id',
                'tickets.*.quantity' => 'required|integer|min:1',
            ]);

            $order = TicketOrder::findOrFail($id);
            $order->ticketOrderDetails()->delete();

            $totalQuantity = 0;
            $totalPrice = 0;
            $ticketDetails = [];

            foreach ($request->tickets as $ticket) {
                $subtotal = $ticket['quantity'] * $ticket['price'];
                $ticketDetails[] = [
                    'ticket_order_id' => $order->id,
                    'ticket_category_id' => $ticket['ticket_category_id'],
                    'quantity' => $ticket['quantity'],
                    'price' => $ticket['price'],
                    'subtotal' => $subtotal,
                ];
                $totalQuantity += $ticket['quantity'];
                $totalPrice += $subtotal;
            }

            TicketOrderDetails::insert($ticketDetails);
            $order->update(['total_quantity' => $totalQuantity, 'total_price' => $totalPrice]);

            return response()->json(['message' => 'Ticket order updated successfully!', 'order' => $order], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to update order', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $order = TicketOrder::findOrFail($id);
            $order->ticketOrderDetails()->delete();
            $order->delete();
            return response()->json(['message' => 'Ticket order deleted successfully!'], 200);
        } catch (Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to delete order', 'error' => $e->getMessage()], 500);
        }
    }
}
