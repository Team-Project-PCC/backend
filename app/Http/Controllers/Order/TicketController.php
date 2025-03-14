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
use App\Models\TicketCategoryDailyQuota;
use Illuminate\Support\Facades\DB;
use App\Models\PromotionUsages;
use App\Models\Event;
use Carbon\Carbon;
use App\Models\EventScheduleRecurring;
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
                        Log::info("Validating event date: $value for event_id: {$request->event_id}");
            
                        $event = Event::find($request->event_id);
                        if (!$event) {
                            Log::error("Event not found with ID: {$request->event_id}");
                            return $fail('Event not found.');
                        }
            
                        Log::info("Event type: {$event->type}");
            
                        $eventDate = Carbon::parse($value);
            
                        if ($event->type === 'special') {
                            Log::info("Checking special event schedule...");
            
                            if (!$event->specialSchedule) {
                                Log::error("Special event has no schedule.");
                                return $fail('Invalid date for this special event.');
                            }
            
                            $specialEventDate = $event->specialSchedule->start_datetime->format('Y-m-d');
                            Log::info("Special event date: $specialEventDate, input date: {$eventDate->format('Y-m-d')}");
            
                            if ($specialEventDate !== $eventDate->format('Y-m-d')) {
                                Log::error("Date mismatch for special event.");
                                return $fail('Invalid date for this special event.');
                            }
                        }
            
                        if ($event->type === 'recurring') {
                            Log::info("Checking recurring event schedule...");
            
                            $eventSchedule = EventScheduleRecurring::where('event_id', $event->id)->first();
                            if (!$eventSchedule) {
                                Log::error("No recurring schedule found for event_id: {$event->id}");
                                return $fail('No recurring schedule found for this event.');
                            }
            
                            Log::info("Recurring type: {$eventSchedule->recurring_type}");
                            $isValidRecurring = false;
            
                            if ($eventSchedule->recurring_type === 'daily') {
                                Log::info("Recurring event is daily, date is always valid.");
                                $isValidRecurring = true;
                            } else if ($eventSchedule->recurring_type === 'weekly') {
                                $dayOfWeek = $eventDate->format('N'); 
                                if($dayOfWeek == 1){
                                    $dayOfWeek = 'monday';
                                } else if($dayOfWeek == 2){
                                    $dayOfWeek = 'tuesday';
                                } else if($dayOfWeek == 3){
                                    $dayOfWeek = 'wednesday';
                                } else if($dayOfWeek == 4){
                                    $dayOfWeek = 'thursday';
                                } else if($dayOfWeek == 5){
                                    $dayOfWeek = 'friday';
                                } else if($dayOfWeek == 6){
                                    $dayOfWeek = 'saturday';
                                } else if($dayOfWeek == 7){
                                    $dayOfWeek = 'sunday';
                                }
                                Log::info("Checking weekly schedule for day: $dayOfWeek");
                                $isValidRecurring = $eventSchedule->scheduleWeekly->where('day', $dayOfWeek)->count() > 0;
                            } else if ($eventSchedule->recurring_type === 'monthly') {
                                $dayOfMonth = $eventDate->day;
                                Log::info("Checking monthly schedule for day: $dayOfMonth");
                                $isValidRecurring = $eventSchedule->monthlySchedules->where('day', $dayOfMonth)->count() > 0;
                            } else if ($eventSchedule->recurring_type === 'yearly') {
                                $dayOfMonth = $eventDate->day;
                                $month = $eventDate->month;
                                Log::info("Checking yearly schedule for day: $dayOfMonth, month: $month");
                                $isValidRecurring = $eventSchedule->yearlySchedules->where('day', $dayOfMonth)->where('month', $month)->count() > 0;
                            }
            
                            if (!$isValidRecurring) {
                                Log::error("Invalid date for this recurring event.");
                                return $fail('Invalid date for this recurring event.');
                            }
                        }
                    },
                ],
            ]);

            $totalQuantity = 0;
            $totalPrice = 0;
            $ticketDetails = [];

            foreach ($request->tickets as $ticket) {
                $ticketCategory = TicketCategory::findOrFail($ticket['ticket_category_id']);
                Log::info("Checking daily quota for ticket category: {$ticket['ticket_category_id']}");
            
                if (!$ticketCategory) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ticket category not found for ID: ' . $ticket['ticket_category_id']
                    ], 404);
                }

                $dailyQuota = TicketCategoryDailyQuota::where('ticket_category_id', $ticketCategory->id)
                    ->where('date', $request->date)
                    ->first();
                if(!$dailyQuota){
                    Log::info("Daily quota not found, creating new quota for ticket category: {$ticketCategory->quota}");
                    TicketCategoryDailyQuota::create([
                        'ticket_category_id' => $ticketCategory->id,
                        'date' => $request->date,
                        'quota' => $ticketCategory->quota,
                    ]);
                    
                } else {
                    if($dailyQuota->quota < $ticket['quantity']){
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Daily quota exceeded for ticket category: ' . $ticketCategory->category
                        ], 400);
                    }
                }

                Log::info("Daily quota available for ticket category:  {$ticket['quantity']}");
                $dailyQuota = TicketCategory::where('id', $ticket['ticket_category_id'])->first();

                if (!$dailyQuota) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Ticket category not found',
                    ], 404);
                }

                $updatedQuota = $dailyQuota->quota - $ticket['quantity'];

                $dailyQuota->update(['quota' => $updatedQuota]);
                $dailyQuota->save();
                
            }

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
                Log::info('Promotion code input: ' . $request->promotion_code);
                
                $promotion = Promotion::where('code', $request->promotion_code)
                    ->where('is_active', true)
                    ->where('valid_from', '<=', now())
                    ->where('valid_until', '>=', now())
                    ->first();

                if (!$promotion) {
                    Log::warning('Promotion not found or not active');
                    return response()->json(['message' => 'Promotion code is invalid or expired'], 400);
                }

                Log::info('Promotion found: ', $promotion->toArray());

                $user = Auth::user();

                if ($promotion->usage_limit > 0) {
                    $usedCount = TicketOrder::where('user_id', $user->id)
                        ->where('promotion_id', $promotion->id)
                        ->count();
                    Log::info("User {$user->id} has used this promo {$usedCount} times");

                    if ($usedCount >= $promotion->usage_limit) {
                        Log::warning('Promotion usage limit exceeded');
                        return response()->json(['message' => 'Promotion code has exceeded usage limit'], 400);
                    }
                }

                $promotionRules = PromotionRules::where('promotion_id', $promotion->id)->get();
                Log::info('Promotion rules: ', $promotionRules->toArray());

                $isValidPromo = true;
                $maxDiscount = null;

                foreach ($promotionRules as $rule) {
                    Log::info("Checking rule: {$rule->rule_type} with value {$rule->rule_value}");
                    
                    if ($rule->rule_type === 'min_order' && $totalPrice < $rule->rule_value) {
                        Log::warning("Order does not meet minimum amount: required {$rule->rule_value}, given {$totalPrice}");
                        $isValidPromo = false;
                        break;
                    }

                    if ($rule->rule_type === 'max_discount') {
                        $maxDiscount = $rule->rule_value;
                        Log::info("Max discount rule applied: {$maxDiscount}");
                    }
                }

                if ($isValidPromo) {
                    if ($promotion->type === 'fixed_discount') {
                        $discount = $promotion->value;
                    } elseif ($promotion->type === 'percentage') {
                        $discount = ($promotion->value / 100) * $totalPrice;
                    }

                    Log::info("Calculated discount: {$discount}");

                    if ($maxDiscount !== null) {
                        $discount = min($discount, $maxDiscount);
                        Log::info("Discount adjusted to max discount: {$discount}");
                    }

                    $totalPrice = max(0, round($totalPrice - $discount, 2));
                    Log::info("Final total price after discount: {$totalPrice}");
                } else {
                    Log::warning("Promotion conditions not met");
                    $promotion = null;
                    $discount = 0;
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
                        'order_id' => $ticketOrder->id . '_' . now()->format('YmdHis'),
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
