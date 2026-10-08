<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\JobReport;
use App\Models\JobPhoto;
use App\Services\ActivityLogger;
use App\Services\NotificationService;

class EmployeePortalController extends Controller
{
    public function jobs(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        $query = Booking::with([
            'customer',
            'assignedEmployee',
            'crew.employee',
            'jobReport.photos',
        ]);

        if ($employee) {
            $query->where(function ($q) use ($employee) {
                $q->where('assigned_employee_id', $employee->id)
                  ->orWhereHas('crew', function ($cq) use ($employee) {
                      $cq->where('employee_id', $employee->id);
                  });
            });
        }

        $jobs = $query->orderBy('scheduled_date', 'asc')->get();

        return response()->json(['success' => true, 'data' => $jobs]);
    }

    public function uploadJobData(Request $request, $id)
    {
        $user = $request->user();
        $booking = Booking::with('customer')->find($id);

        if (!$booking) {
            return response()->json(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $status = $request->input('status', 'IN_PROGRESS');
        $faultNotes = $request->input('faultNotes');
        $workDescription = $request->input('workDescription');
        $treatmentApplied = $request->input('treatmentApplied');
        $checklist = $request->input('checklist');
        $customerSignature = $request->input('customerSignature');
        $summaryNotes = $request->input('summaryNotes');

        $jobReport = JobReport::firstOrCreate(
            ['booking_id' => $booking->id],
            [
                'employee_id' => $user->employee?->id ?: $booking->assigned_employee_id,
                'visit_time' => now(),
                'status' => $status,
            ]
        );

        $reportUpdates = ['status' => $status];
        if ($request->has('faultNotes')) $reportUpdates['fault_notes'] = $faultNotes;
        if ($request->has('workDescription')) $reportUpdates['work_description'] = $workDescription;
        if ($request->has('treatmentApplied')) $reportUpdates['treatment_applied'] = $treatmentApplied;
        if ($request->has('checklist')) $reportUpdates['checklist'] = is_array($checklist) ? json_encode($checklist) : $checklist;
        if ($request->has('customerSignature')) $reportUpdates['customer_signature'] = $customerSignature;
        if ($request->has('summaryNotes')) $reportUpdates['summary_notes'] = $summaryNotes;
        if ($status === 'COMPLETED') $reportUpdates['completion_time'] = now();

        $jobReport->update($reportUpdates);

        // Upload photos
        $photos = $request->input('photos');
        $photoUrl = $request->input('photoUrl');
        $type = $request->input('type', 'BEFORE');
        $caption = $request->input('caption');

        if (is_array($photos)) {
            foreach ($photos as $p) {
                if (!empty($p['photoUrl'])) {
                    JobPhoto::create([
                        'job_report_id' => $jobReport->id,
                        'photo_url' => $p['photoUrl'],
                        'type' => $p['type'] ?? $type,
                        'caption' => $p['caption'] ?? $caption,
                        'uploaded_by' => $user->name,
                    ]);
                }
            }
        } elseif ($photoUrl) {
            JobPhoto::create([
                'job_report_id' => $jobReport->id,
                'photo_url' => $photoUrl,
                'type' => $type,
                'caption' => $caption,
                'uploaded_by' => $user->name,
            ]);
        }

        // Update booking status
        if ($status === 'COMPLETED') {
            $booking->update(['status' => 'COMPLETED']);
            NotificationService::create(
                title: '🎉 Service Completed Successfully!',
                message: "Your {$booking->service_name} has been completed. Check out your technician's inspection photos.",
                type: 'SUCCESS',
                roleTarget: 'CUSTOMER',
                userId: $booking->customer?->user_id,
                link: '/dashboard?tab=photos'
            );
        } elseif ($status === 'IN_PROGRESS' && $booking->status !== 'COMPLETED') {
            $booking->update(['status' => 'IN_PROGRESS']);
        }

        if ($type === 'FAULT_EVIDENCE' || $faultNotes) {
            NotificationService::create(
                title: '⚠️ Pre-Existing Condition Logged',
                message: "Technician noted a condition on your property for Booking #{$booking->booking_number}.",
                type: 'WARNING',
                roleTarget: 'CUSTOMER',
                userId: $booking->customer?->user_id,
                link: '/dashboard?tab=photos'
            );
        }

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'FieldTechnician',
            entityId: (string) $booking->id,
            details: ['status' => $status, 'bookingNumber' => $booking->booking_number],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Inspection notes and photos uploaded successfully',
            'data' => $jobReport->fresh('photos'),
        ]);
    }
}
