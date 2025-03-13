<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Event;
use App\Models\TicketCategory;
use App\Models\TicketOrder;
use App\Models\TicketOrderDetails;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\PromotionUsages;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class OrderSeeder extends Seeder
{
    public function run()
    {
        DB::beginTransaction();
        try {
            $users = User::inRandomOrder()->limit(10)->get();
            $event = Event::inRandomOrder()->first();
            $ticketCategories = TicketCategory::where('event_id', $event->id)->get();
            $promotion = Promotion::where('is_active', true)->inRandomOrder()->first();

            foreach ($users as $user) {
                $totalQuantity = 0;
                $totalPrice = 0;
                $ticketDetails = [];

                foreach ($ticketCategories as $category) {
                    $quantity = rand(1, 3);
                    $subtotal = $quantity * $category->price;

                    $ticketDetails[] = [
                        'ticket_category_id' => $category->id,
                        'quantity' => $quantity,
                        'price' => $category->price,
                        'subtotal' => $subtotal,
                    ];

                    $totalQuantity += $quantity;
                    $totalPrice += $subtotal;
                }

                $discount = 0;
                if ($promotion) {
                    $discount = $promotion->type === 'percentage' 
                        ? ($promotion->value / 100) * $totalPrice 
                        : $promotion->value;
                    $totalPrice = max(0, $totalPrice - $discount);
                }

                $ticketOrder = TicketOrder::create([
                    'user_id' => $user->id,
                    'event_id' => $event->id,
                    'total_quantity' => $totalQuantity,
                    'total_price' => round($totalPrice, 2),
                    'promotion_id' => $promotion ? $promotion->id : null,
                ]);

                foreach ($ticketDetails as $detail) {
                    TicketOrderDetails::create([
                        'ticket_order_id' => $ticketOrder->id,
                        'ticket_category_id' => $detail['ticket_category_id'],
                        'quantity' => $detail['quantity'],
                        'price' => $detail['price'],
                        'subtotal' => $detail['subtotal'],
                    ]);
                }

                Payment::create([
                    'method' => 'cashless',
                    'amount' => $totalPrice,
                    'ticket_order_id' => $ticketOrder->id,
                    'user_id' => $user->id,
                    'status' => 'pending',
                ]);

                if ($promotion) {
                    PromotionUsages::create([
                        'promotion_id' => $promotion->id,
                        'user_id' => $user->id,
                        'order_id' => $ticketOrder->id,
                    ]);
                    $promotion->increment('current_usage');
                }
            }

            DB::commit();
            Log::info('10 Ticket Orders successfully created!');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Failed to create ticket orders: ' . $e->getMessage());
        }
    }
}
