<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Enquiry;
use App\Models\Booking;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Service;
use App\Models\Blog;
use App\Models\ActivityLog;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        $enquiriesCount = Enquiry::count();
        $newEnquiriesCount = Enquiry::where('status', 'NEW')->count();
        $bookingsCount = Booking::count();
        $confirmedBookingsCount = Booking::whereIn('status', ['CONFIRMED', 'IN_PROGRESS', 'COMPLETED'])->count();
        $ordersCount = Order::count();
        $totalRevenue = Order::where('payment_status', 'PAID')->sum('total_amount') ?: 0;
        $customersCount = Customer::count();
        $servicesCount = Service::where('is_active', true)->count();
        $blogsCount = Blog::where('status', 'PUBLISHED')->count();

        $recentEnquiries = Enquiry::orderBy('created_at', 'desc')->take(6)->get();
        $recentBookings = Booking::orderBy('created_at', 'desc')->take(6)->get();
        $recentLogs = ActivityLog::orderBy('created_at', 'desc')->take(8)->get();

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'enquiriesCount' => $enquiriesCount,
                    'newEnquiriesCount' => $newEnquiriesCount,
                    'bookingsCount' => $bookingsCount,
                    'confirmedBookingsCount' => $confirmedBookingsCount,
                    'ordersCount' => $ordersCount,
                    'totalRevenue' => $totalRevenue,
                    'customersCount' => $customersCount,
                    'servicesCount' => $servicesCount,
                    'blogsCount' => $blogsCount,
                ],
                'recentEnquiries' => $recentEnquiries,
                'recentBookings' => $recentBookings,
                'recentLogs' => $recentLogs,
            ],
        ]);
    }
}
