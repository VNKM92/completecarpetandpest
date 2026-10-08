<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\ActivityLogger;

class AdminOrderController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $status = $request->query('status');

        $query = Order::with(['booking', 'customer']);

        if ($status && $status !== 'ALL') {
            $query->where('payment_status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $orders]);
    }

    public function update(Request $request, $id = null)
    {
        $orderId = $id ?: $request->input('id');
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }

        $updateData = [];
        if ($request->has('paymentStatus')) $updateData['payment_status'] = $request->paymentStatus;
        if ($request->has('paymentMethod')) $updateData['payment_method'] = $request->paymentMethod;
        if ($request->has('notes')) $updateData['notes'] = $request->notes;

        $order->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Orders',
            entityId: (string) $order->id,
            details: $updateData,
            request: $request
        );

        return response()->json(['success' => true, 'data' => $order->fresh(['booking', 'customer'])]);
    }
}
