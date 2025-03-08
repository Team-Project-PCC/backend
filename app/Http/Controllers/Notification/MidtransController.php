<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Notification;
use App\Models\Payment;
use Exception;

class MidtransController extends Controller
{
    public function handleMidtransNotification(Request $request)
    {
        try {
            $notif = new Notification();

            $transaction = $notif->transaction_status;
            $orderId = $notif->order_id;
            $fraudStatus = $notif->fraud_status;

            Log::info("Midtrans Notification received for Order ID: $orderId with status: $transaction");

            // Cari pembayaran berdasarkan ID transaksi
            $payment = Payment::where('id', $orderId)->first();

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            // Perbarui status berdasarkan Midtrans notification
            if ($transaction == 'capture') {
                if ($fraudStatus == 'accept') {
                    $payment->update(['status' => 'success']);
                } else {
                    $payment->update(['status' => 'denied']); // Jika fraud status bukan 'accept'
                }
            } elseif ($transaction == 'settlement') {
                $payment->update(['status' => 'success']);
            } elseif ($transaction == 'pending') {
                $payment->update(['status' => 'pending']);
            } elseif ($transaction == 'deny') {
                $payment->update(['status' => 'denied']); // Transaksi ditolak oleh Midtrans
            } elseif ($transaction == 'cancel') {
                $payment->update(['status' => 'cancelled']); // Transaksi dibatalkan oleh user
            } elseif ($transaction == 'expire') {
                $payment->update(['status' => 'expired']);
            } elseif ($transaction == 'refund') {
                $payment->update(['status' => 'refunded']);
            }

            return response()->json(['message' => 'Notification processed successfully'], 200);
        } catch (Exception $e) {
            Log::error("Midtrans Notification Error: " . $e->getMessage());
            return response()->json(['message' => 'Failed to process notification'], 500);
        }
    }

}
