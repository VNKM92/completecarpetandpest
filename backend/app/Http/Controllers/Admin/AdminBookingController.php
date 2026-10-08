<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Services\ActivityLogger;

class AdminBookingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search', '');
        $status = $request->query('status', '');
        $page = intval($request->query('page', 1));
        $limit = intval($request->query('limit', 15));

        $query = Booking::with(['order', 'customer', 'assignedEmployee', 'crew.employee', 'jobReport.photos']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('customer_phone', 'like', "%{$search}%")
                  ->orWhere('service_name', 'like', "%{$search}%")
                  ->orWhere('service_address', 'like', "%{$search}%");
            });
        }

        $total = $query->count();
        $items = $query->orderBy('created_at', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => ceil($total / max(1, $limit)),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string',
            'customerEmail' => 'required|email',
            'customerPhone' => 'required|string',
            'serviceName' => 'required|string',
            'serviceAddress' => 'required|string',
        ]);

        $count = Booking::count();
        $bookingNumber = 'BK-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'customer_id' => $request->customerId,
            'customer_name' => $request->customerName,
            'customer_email' => $request->customerEmail,
            'customer_phone' => $request->customerPhone,
            'service_id' => $request->serviceId,
            'service_name' => $request->serviceName,
            'service_address' => $request->serviceAddress,
            'suburb' => $request->suburb,
            'postcode' => $request->postcode,
            'scheduled_date' => $request->scheduledDate ? date('Y-m-d H:i:s', strtotime($request->scheduledDate)) : null,
            'time_slot' => $request->timeSlot ?: 'Morning (8AM - 11AM)',
            'square_footage' => $request->squareFootage,
            'rooms' => $request->rooms,
            'bathrooms' => $request->bathrooms,
            'addons' => is_array($request->addons) ? json_encode($request->addons) : $request->addons,
            'total_price' => floatval($request->totalPrice ?: 0),
            'approved_price' => $request->approvedPrice ? floatval($request->approvedPrice) : null,
            'deposit_required' => floatval($request->depositRequired ?: 50.0),
            'deposit_paid' => floatval($request->depositPaid ?: 0.0),
            'balance_due' => floatval($request->balanceDue ?: ($request->totalPrice ?: 0)),
            'quote_status' => $request->quoteStatus ?: 'PENDING_APPROVAL',
            'status' => $request->status ?: 'PENDING',
            'payment_status' => $request->paymentStatus ?: 'UNPAID',
            'notes' => $request->notes,
            'admin_notes' => $request->adminNotes,
            'assigned_employee_id' => $request->assignedEmployeeId,
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Bookings',
            entityId: (string) $booking->id,
            details: ['bookingNumber' => $bookingNumber, 'customer' => $request->customerName],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $booking], 201);
    }

    public function update(Request $request, $id = null)
    {
        $bookingId = $id ?: $request->input('id');
        if (!$bookingId) {
            return response()->json(['success' => false, 'message' => 'Missing booking ID'], 400);
        }

        $booking = Booking::find($bookingId);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $updateData = [];
        if ($request->has('status')) $updateData['status'] = $request->status;
        if ($request->has('notes')) $updateData['notes'] = $request->notes;
        if ($request->has('adminNotes')) $updateData['admin_notes'] = $request->adminNotes;
        if ($request->has('timeSlot')) $updateData['time_slot'] = $request->timeSlot;
        if ($request->has('totalPrice')) $updateData['total_price'] = floatval($request->totalPrice);
        if ($request->has('approvedPrice')) $updateData['approved_price'] = floatval($request->approvedPrice);
        if ($request->has('depositPaid')) $updateData['deposit_paid'] = floatval($request->depositPaid);
        if ($request->has('balanceDue')) $updateData['balance_due'] = floatval($request->balanceDue);
        if ($request->has('paymentStatus')) $updateData['payment_status'] = $request->paymentStatus;
        if ($request->has('quoteStatus')) $updateData['quote_status'] = $request->quoteStatus;
        if ($request->has('scheduledDate')) {
            $updateData['scheduled_date'] = $request->scheduledDate ? date('Y-m-d H:i:s', strtotime($request->scheduledDate)) : null;
        }
        if ($request->has('assignedEmployeeId')) {
            $updateData['assigned_employee_id'] = $request->assignedEmployeeId ?: null;
        }

        $booking->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Bookings',
            entityId: (string) $booking->id,
            details: $updateData,
            request: $request
        );

        return response()->json(['success' => true, 'data' => $booking->fresh(['order', 'customer'])]);
    }

    public function destroy(Request $request, $id = null)
    {
        $bookingId = $id ?: $request->query('id', $request->input('id'));
        if (!$bookingId) {
            return response()->json(['success' => false, 'message' => 'Missing booking ID'], 400);
        }

        $booking = Booking::find($bookingId);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $bookingNumber = $booking->booking_number;
        $booking->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Bookings',
            entityId: (string) $bookingId,
            details: ['bookingNumber' => $bookingNumber],
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Booking deleted']);
    }
}
