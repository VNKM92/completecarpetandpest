<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use App\Services\ActivityLogger;

class AdminEmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with([
            'user',
            'assignedBookings' => function ($q) {
                $q->orderBy('scheduled_date', 'desc')->take(10);
            },
            'attendances' => function ($q) {
                $q->orderBy('date', 'desc')->take(7);
            },
            'jobReports.photos',
            'crewAssignments.booking',
        ])->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $employees]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'required|string',
            'designation' => 'required|string',
        ]);

        $cleanEmail = strtolower(trim($request->email));

        $staffRole = Role::firstOrCreate(
            ['slug' => 'staff'],
            ['name' => 'Staff', 'description' => 'Field technician / cleaning specialist', 'is_system' => true]
        );

        $user = User::updateOrCreate(
            ['email' => $cleanEmail],
            [
                'name' => $request->name,
                'phone' => $request->phone,
                'password' => Hash::make($request->password ?: 'Staff@123456'),
                'role_id' => $staffRole->id,
                'status' => 'ACTIVE',
            ]
        );

        $count = Employee::count();
        $employeeCode = 'EMP-' . str_pad($count + 101, 3, '0', STR_PAD_LEFT);

        $employee = Employee::create([
            'employee_code' => $employeeCode,
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $cleanEmail,
            'phone' => $request->phone,
            'designation' => $request->designation,
            'department' => $request->department ?: 'Cleaning & Pest Control',
            'hourly_rate' => floatval($request->hourlyRate ?: 35.0),
            'skills' => is_array($request->skills) ? json_encode($request->skills) : ($request->skills ?: '["Carpet Cleaning", "Pest Control"]'),
            'emergency_contact' => $request->emergencyContact,
            'license_number' => $request->licenseNumber,
            'address' => $request->address,
            'status' => 'ACTIVE',
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'HRM',
            entityId: (string) $employee->id,
            details: ['employeeCode' => $employeeCode, 'name' => $request->name, 'designation' => $request->designation],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => "Employee {$request->name} ({$employeeCode}) onboarded successfully!",
            'data' => $employee->fresh('user'),
        ], 201);
    }

    public function update(Request $request, $id = null)
    {
        $employeeId = $id ?: $request->input('id');
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $updateData = $request->only([
            'name', 'phone', 'designation', 'department', 'status',
            'hourly_rate', 'skills', 'emergency_contact', 'license_number', 'address',
        ]);

        if (isset($updateData['skills']) && is_array($updateData['skills'])) {
            $updateData['skills'] = json_encode($updateData['skills']);
        }
        if (isset($updateData['hourlyRate'])) {
            $updateData['hourly_rate'] = floatval($updateData['hourlyRate']);
        }

        $employee->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'HRM',
            entityId: (string) $employee->id,
            details: $updateData,
            request: $request
        );

        return response()->json(['success' => true, 'data' => $employee->fresh('user')]);
    }

    public function destroy(Request $request, $id = null)
    {
        $employeeId = $id ?: $request->query('id', $request->input('id'));
        $employee = Employee::find($employeeId);
        if (!$employee) {
            return response()->json(['success' => false, 'message' => 'Employee not found'], 404);
        }

        $employee->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'HRM',
            entityId: (string) $employeeId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Employee record removed']);
    }
}
