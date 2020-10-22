<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Order;
use App\paymentLog;

class WebhookController extends Controller
{
    public function midtransWebhook(Request $request) {
        $data = $request->all();

        $signature = $data['signature_key'];
        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');
        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        $mysignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if ($signature !== $mysignature) {
            return response()->json([
                'status' => 'error',
                'message' => 'invalid signature'
            ]);
        }

        $rawOrderId = explode('-', $orderId);
        $order = Order::find($rawOrderId[0]);
        // dd($order);

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'id not found'
            ]);
        }

        if($order->status == 'success') {
            return response()->json([
                'status' => 'error',
                'message' => 'payment already success'
            ]);
        }

        if ($transactionStatus === 'capture'){
            if ($fraudStatus == 'challenge'){
                $order->update([
                    'status' => 'challenge'
                ]);
            } else if ($fraudStatus == 'accept'){
                $order->update([
                    'status' => 'success'
                ]);
            }
        } else if ($transactionStatus === 'settlement'){
            $order->update([
                'status' => 'success'
            ]);
        } else if ($transactionStatus == 'cancel' ||
          $transactionStatus === 'deny' ||
          $transactionStatus === 'expire'){
            $order->update([
                'status' => 'failed'
            ]);
        } else if ($transactionStatus === 'pending'){
            $order->update([
                'status' => 'pending'
            ]);
        }

        $paymentLog =  [
            'order_id' => $rawOrderId,
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'payment_type' => $type 
        ];

        PaymentLog::create($paymentLog);
        return response()->json('ok');
    }
}
