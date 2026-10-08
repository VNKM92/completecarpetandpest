<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\BookingCrewMember;
use App\Services\ActivityLogger;
use App\Services\NotificationService;

class AdminAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $bookingId = $request->query('bookingId');
        $employeeId = $request->query('employeeId');

        $query = BookingCrewMember::with(['employee', 'booking.customer', 'booking.jobReport.photos']);

        if ($bookingId) {
            $query->where('booking_id', $bookingId);
        }
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $crewMembers = $query->orderBy('assigned_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $crewMembers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'bookingId' => 'required',
            'crew' => 'required|array',
        ]);

        $booking = Booking::with('customer')->find($request->bookingId);
        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $leadMember = null;
        foreach ($request->crew as $c) {
            if (!empty($c['isLead'])) {
                $leadMember = $c;
                break;
            }
        }
        if (!$leadMember && count($request->crew) > 0) {
            $leadMember = $request->crew[0];
        }

        $mainLeadId = $request->primaryEmployeeId ?: ($leadMember['employeeId'] ?? null);

        // Delete existing crew
        BookingCrewMember::where('booking_id', $request->bookingId)->delete();

        $createdCrew = [];
        foreach ($request->crew as $member) {
            if (!empty($member['employeeId'])) {
                $record = BookingCrewMember::create([
                    'booking_id' => $request->bookingId,
                    'employee_id' => $member['employeeId'],
                    'role' => $member['role'] ?? 'Technician',
                    'is_lead' => !empty($member['isLead']) || $member['employeeId'] === $mainLeadId,
                    'notes' => $member['notes'] ?? null,
                ]);

                $record->load('employee');
                $createdCrew[] = $record;

                if ($record->employee && $record->employee->user_id) {
                    NotificationService::create(
                        title: "Assigned to Job #{$booking->booking_number}",
                        message: "You have been assigned as {$record->role} for {$booking->service_name} at {$booking->service_address}",
                        type: 'INFO',
                        roleTarget: 'EMPLOYEE',
                        userId: $record->employee->user_id,
                        link: '/employee'
                    );
                }
            }
        }

        $booking->update(['assigned_employee_id' => $mainLeadId]);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'TechnicianAssignment',
            entityId: (string) $booking->id,
            details: [
                'bookingNumber' => $booking->booking_number,
                'crewSize' => count($createdCrew),
            ],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Successfully assigned ' . count($createdCrew) . ' technician(s) to booking #' . $booking->booking_number,
            'data' => $booking->fresh(['assignedEmployee', 'crew.employee', 'customer']),
        ]);
    }
}
