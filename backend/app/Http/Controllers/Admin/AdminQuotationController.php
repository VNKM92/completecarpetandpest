<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\PaymentTransaction;
use App\Services\ActivityLogger;
use App\Services\NotificationService;

class AdminQuotationController extends Controller
{
    public function index(Request $request)
    {
        $quoteStatus = $request->query('status');
        $search = $request->query('search');

        $query = Booking::with([
            'customer',
            'assignedEmployee',
            'crew.employee',
            'jobReport.photos',
            'invoices',
            'payments',
        ]);

        if ($quoteStatus && $quoteStatus !== 'ALL') {
            $query->where('quote_status', $quoteStatus);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('service_name', 'like', "%{$search}%")
                  ->orWhere('service_address', 'like', "%{$search}%");
            });
        }

        $quotations = $query->orderBy('created_at', 'desc')->get();

        $totalPending = Booking::where('quote_status', 'PENDING_APPROVAL')->count();
        $totalApproved = Booking::where('quote_status', 'APPROVED')->count();
        $totalConfirmed = Booking::where('status', 'CONFIRMED')->count();
        $totalRevenue = PaymentTransaction::where('status', 'SUCCESS')->sum('amount') ?: 0;

        return response()->json([
            'success' => true,
            'data' => [
                'quotations' => $quotations,
                'stats' => [
                    'totalPending' => $totalPending,
                    'totalApproved' => $totalApproved,
                    'totalConfirmed' => $totalConfirmed,
                    'totalRevenue' => floatval($totalRevenue),
                ],
            ],
        ]);
    }

    public function show(Request $request, $id)
    {
        $booking = Booking::with([
            'customer',
            'assignedEmployee',
            'crew.employee',
            'jobReport.photos',
            'jobReport.employee',
            'invoices.items',
            'payments',
            'reminderLogs',
        ])->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Quotation not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $booking]);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::with('customer')->find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Quotation not found'], 404);
        }

        $action = $request->action;
        $updateData = [];

        if ($request->has('adminNotes')) $updateData['admin_notes'] = $request->adminNotes;
        if ($request->has('timeSlot')) $updateData['time_slot'] = $request->timeSlot;
        if ($request->has('status')) $updateData['status'] = $request->status;
        if ($request->has('scheduledDate')) {
            $updateData['scheduled_date'] = $request->scheduledDate ? date('Y-m-d H:i:s', strtotime($request->scheduledDate)) : null;
        }
        if ($request->has('assignedEmployeeId')) {
            $updateData['assigned_employee_id'] = $request->assignedEmployeeId ?: null;
        }

        if ($action === 'APPROVE') {
            $finalPrice = $request->approvedPrice ? floatval($request->approvedPrice) : $booking->total_price;
            $depositAmt = $request->depositRequired ? floatval($request->depositRequired) : 50.0;

            $updateData['quote_status'] = 'APPROVED';
            $updateData['approved_price'] = $finalPrice;
            $updateData['deposit_required'] = $depositAmt;
            $updateData['balance_due'] = max(0, $finalPrice - ($booking->deposit_paid ?: 0));

            $booking->update($updateData);

            NotificationService::create(
                title: '🎉 Quotation Approved!',
                message: "Your quotation #{$booking->booking_number} for {$booking->service_name} has been approved at \${$finalPrice} AUD.",
                type: 'SUCCESS',
                roleTarget: 'CUSTOMER',
                userId: $booking->customer?->user_id,
                link: '/dashboard?tab=quotations'
            );

            ActivityLogger::log(
                action: 'APPROVAL',
                module: 'Quotations',
                entityId: (string) $booking->id,
                details: ['bookingNumber' => $booking->booking_number, 'approvedPrice' => $finalPrice, 'depositRequired' => $depositAmt],
                request: $request
            );

            return response()->json([
                'success' => true,
                'message' => "Quotation approved! Total: \${$finalPrice} AUD, Deposit required: \${$depositAmt} AUD. Customer has been notified.",
                'data' => $booking->fresh(['customer', 'assignedEmployee']),
            ]);
        }

        if ($action === 'REJECT') {
            $updateData['quote_status'] = 'REJECTED';
            $updateData['status'] = 'CANCELLED';
            $updateData['rejection_reason'] = $request->rejectionReason ?: 'Requirements outside our current service scope.';

            $booking->update($updateData);

            ActivityLogger::log(
                action: 'STATUS_CHANGE',
                module: 'Quotations',
                entityId: (string) $booking->id,
                details: ['bookingNumber' => $booking->booking_number, 'status' => 'REJECTED', 'reason' => $request->rejectionReason],
                request: $request
            );

            return response()->json([
                'success' => true,
                'message' => 'Quotation marked as rejected.',
                'data' => $booking->fresh(['customer', 'assignedEmployee']),
            ]);
        }

        $booking->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Quotation updated successfully',
            'data' => $booking->fresh(['customer', 'assignedEmployee']),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $booking = Booking::find($id);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Quotation not found'], 404);
        }

        $bookingNumber = $booking->booking_number;
        $booking->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Quotations',
            entityId: (string) $id,
            details: ['bookingNumber' => $bookingNumber],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Quotation / Booking deleted successfully by Super Admin',
        ]);
    }
}
