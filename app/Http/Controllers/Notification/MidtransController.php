<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;
use App\Models\Payment;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Models\TicketOrder;

class MidtransController extends Controller
{
    public function callback(Request $request)
    {
        
        $serverKey = config('services.midtrans.serverKey');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json([
                'message' => 'Invalid signature'], 403);
        }

        DB::beginTransaction();
        try {
            $payment = Payment::where('id', $request->order_id)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            $ticketOrder = TicketOrder::where('id', $payment->ticket_order_id)->first();

            if (!$ticketOrder) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            switch ($request->transaction_status) {
                case 'settlement':
                case 'capture':
                    $payment->status = 'finished';
                    $ticketOrder->status = 'paid';
                    break;
                case 'pending':
                    $payment->status = 'pending';
                    break;
                case 'expire':
                case 'cancel':
                case 'deny':
                    $payment->status = 'failed';
                    $ticketOrder->status = 'unpaid';
                    break;
            }

            $payment->save();
            $ticketOrder->save();
            
            DB::commit();
            return response()->json(['message' => 'Payment status updated successfully']);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Midtrans Callback Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating payment status',
                'error' => $e
            ], 500);
        }
    }

    public function handleStatus(Request $request)
    {
        try{
            $order_id = $request->order_id;
            $first_part = substr($order_id, 0, strpos($order_id, "_"));
            $payment = Payment::where('id', $first_part)->first();

            if (!$payment) {
    return response()->json(['message' => 'Payment not found'], 404);
    } else {
        if ($request->transaction_status == 'settlement') {
            $payment->status = 'success';
        } else if ($request->transaction_status == 'pending') {
            $payment->status = 'pending';
        } else if ($request->transaction_status == 'expire') {
            $payment->status = 'expired';
        } else if ($request->transaction_status == 'cancel') {
            $payment->status = 'cancelled';
        } else if ($request->transaction_status == 'deny') {
            $payment->status = 'denied';
        } else if ($request->transaction_status == 'failed') {
            $payment->status = 'failed';
        } else {
            return response()->json(['message' => 'Invalid status'], 400);
        }
    
        $payment->save();
        Log::info('Midtrans Handle Status - Payment updated', [
            'order_id' => $payment->order_id,
            'status' => $payment->status
        ]);
    
        return response()->json(['message' => 'Payment status updated to ' . $payment->status]);
    }
        } catch (Exception $e) {
            Log::error('Midtrans Handle Status Error: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error updating payment status',
                'error' => $e->getMessage()
            ], 500);

        }
    }
}