<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ticket;

class TicketController extends Controller
{
    public function index(){
        try{
            $tickets = Ticket::all();
            return response()->json([
                'status' => 'success',
                'ticket' => $tickets,
                'ticket orders' => $tickets->ticket_orders,
                'ticket order details' => $tickets->ticket_order_details
            ]);
        } catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
