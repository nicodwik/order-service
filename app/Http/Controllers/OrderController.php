<?php

namespace App\Http\Controllers;

use App\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request) {
        $userId = \Request('user_id');
        $order = Order::query();

        // if ($userId) {
        //     $order->where('user_id', $userId);
        // }

        $order->when($userId, function($query) use ($userId) {
            return $query->where('user_id', $userId);
        });

        $order = $order->get();

        return response()->json([
            'status' => 'success',
            'data' => $order
        ]);
    }

    public function create(Request $request) {
        $data = $request->all();
        $user = $data['user'];
        $course = $data['course'];
        
        $order = Order::create([
            'user_id' => $user['id'],
            'course_id' => $course['id']
        ]);

        $transactionDetails = [
            'order_id' => $order->id . \Str::random(5),
            'gross_amount' => $course['price']
        ];

        $itemDetails = [
            [
                'id' => $course['id'],
                'price' => $course['price'],
                'quantity' => 1,
                'name' => $course['name']
            ]
        ];

        $customerDetail = [
            'first_name' => $user['name'],
            'email' => $user['email']
        ];

        $midtransParams = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            'customer_details' => $customerDetail
        ];

        $snapUrl = $this->getMidtransSnapUrl($midtransParams);
        
        $order->update([
            'snap_url' => $snapUrl,
            'metadata' => [
                'course_id' => $course['id'],
                'course_name' => $course['name'],
                'course_price' => $course['price'],
                'course_thumbnail' => $course['thumbnail'],
                'course_level' => $course['level'],
            ]
        ]);
        
        return response()->json([
            'status'=> 'success',
            'data' => $order
        ]);
    }

    private function getMidtransSnapUrl($params) {
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_ISPRODUCTION');
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_IS3DS');

        $snap_url = \Midtrans\Snap::createTransaction($params)->redirect_url;
        return $snap_url;
    }
}
