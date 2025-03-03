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
            $validated = $request->validate([
                'event_id' => 'required|integer',
                'quantity' => 'required|integer',
                'promotion_id' => 'nullable|integer',
                'total_price' => 'required|numeric',
                'payment_method' => 'required|string',
                'status' => 'required|string',
                'user_id' => 'required|integer',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to order ticket',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
