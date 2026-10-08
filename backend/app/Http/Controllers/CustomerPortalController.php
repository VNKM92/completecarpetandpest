<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Notification;

class CustomerPortalController extends Controller
{
    public function quotations(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        $query = Booking::with([
            'assignedEmployee',
            'crew.employee',
            'jobReport.photos',
            'invoices',
            'payments',
        ]);

        if ($customer) {
            $query->where(function ($q) use ($customer, $user) {
                $q->where('customer_id', $customer->id)
                  ->orWhere('customer_email', $user->email);
            });
        } else {
            $query->where('customer_email', $user->email);
        }

        $quotations = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $quotations]);
    }

    public function invoices(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        $query = Invoice::with(['items', 'booking', 'payments']);

        if ($customer) {
            $query->where(function ($q) use ($customer, $user) {
                $q->where('customer_id', $customer->id)
                  ->orWhere('customer_email', $user->email);
            });
        } else {
            $query->where('customer_email', $user->email);
        }

        $invoices = $query->orderBy('issued_date', 'desc')->get();

        return response()->json(['success' => true, 'data' => $invoices]);
    }

    public function payments(Request $request)
    {
        $user = $request->user();
        $customer = $user->customer;

        $query = PaymentTransaction::with(['booking', 'invoice']);

        if ($customer) {
            $query->where(function ($q) use ($customer, $user) {
                $q->where('customer_id', $customer->id)
                  ->orWhere('payer_email', $user->email);
            });
        } else {
            $query->where('payer_email', $user->email);
        }

        $payments = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $payments]);
    }

    public function notifications(Request $request)
    {
        $user = $request->user();

        $notifications = Notification::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('role_target', 'CUSTOMER')
              ->orWhere('role_target', 'ALL');
        })->orderBy('created_at', 'desc')->take(30)->get();

        return response()->json(['success' => true, 'data' => $notifications]);
    }

    public function markNotificationsRead(Request $request)
    {
        $user = $request->user();

        Notification::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhere('role_target', 'CUSTOMER');
        })->where('is_read', false)->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Notifications marked as read']);
    }
}
