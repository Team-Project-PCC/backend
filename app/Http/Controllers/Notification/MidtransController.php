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
        $serverKey = config('midtrans.server_key');
        $hashed = hash("sha512", $request->order_id . $request->status_code . $request->gross_amount . $serverKey);

        if ($hashed !== $request->signature_key) {
            return response()->json(['message' => 'Invalid signature'], 403);
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
                case 'settlement': // Payment successful
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
            return response()->json(['message' => 'Error updating payment status'], 500);
        }
    }

    public function success(Request $request)
    {
        try{
            $payment = Payment::where('order_id', $request->order_id)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            } else{
                $payment->status = 'success';
                $payment->save();
            }

            return response()->json(['message' => 'Payment success']);
        } catch (Exception $e) {
            Log::error('Midtrans Success Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating payment status'], 500);
        }
    }

    public function failed(Request $request)
    {
        try{
            $payment = Payment::where('order_id', $request->order_id)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            } else{
                $payment->status = 'failed';
                $payment->save();
            }

            return response()->json(['message' => 'Payment failed']);
        } catch (Exception $e) {
            Log::error('Midtrans Failed Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating payment status'], 500);
        }
    }

    public function cancel(Request $request)
    {
        try{
            $payment = Payment::where('order_id', $request->order_id)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            } else{
                $payment->status = 'cancelled';
                $payment->save();
            }

            return response()->json(['message' => 'Payment cancelled']);
        } catch (Exception $e) {
            Log::error('Midtrans Cancel Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating payment status'], 500);
        }
    }
}
