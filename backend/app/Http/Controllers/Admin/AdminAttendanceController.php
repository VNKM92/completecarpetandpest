<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Services\ActivityLogger;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->query('employeeId');
        $date = $request->query('date');

        $query = Attendance::with('employee');

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        if ($date) {
            $query->whereDate('date', $date);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        return response()->json(['success' => true, 'data' => $attendances]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employeeId' => 'required',
            'date' => 'required',
            'status' => 'required|string',
        ]);

        $date = date('Y-m-d', strtotime($request->date));

        $attendance = Attendance::updateOrCreate(
            [
                'employee_id' => $request->employeeId,
                'date' => $date,
            ],
            [
                'check_in' => $request->checkIn ? date('Y-m-d H:i:s', strtotime($request->checkIn)) : null,
                'check_out' => $request->checkOut ? date('Y-m-d H:i:s', strtotime($request->checkOut)) : null,
                'status' => $request->status,
                'work_notes' => $request->workNotes,
            ]
        );

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Attendance',
            entityId: (string) $attendance->id,
            details: ['employeeId' => $request->employeeId, 'date' => $date, 'status' => $request->status],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance record logged successfully',
            'data' => $attendance->fresh('employee'),
        ]);
    }
}
