<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\Customer;
use App\Models\Booking;

class AdminReportController extends Controller
{
    public function technicians(Request $request)
    {
        $employeeId = $request->query('employeeId');
        $customerId = $request->query('customerId');

        // 1. Fetch Employees
        $empQuery = Employee::with([
            'assignedBookings.customer',
            'assignedBookings.jobReport.photos',
            'assignedBookings.crew.employee',
            'crewAssignments.booking.customer',
            'crewAssignments.booking.jobReport.photos',
            'crewAssignments.booking.crew.employee',
        ]);

        if ($employeeId) {
            $empQuery->where('id', $employeeId);
        }

        $employees = $empQuery->orderBy('name', 'asc')->get();

        $technicianReports = [];
        foreach ($employees as $emp) {
            $allBookingMap = [];

            foreach ($emp->assignedBookings as $b) {
                $allBookingMap[$b->id] = [
                    'booking' => $b,
                    'role' => 'Lead Specialist (Primary)',
                    'isLead' => true,
                ];
            }

            foreach ($emp->crewAssignments as $ca) {
                if ($ca->booking) {
                    $allBookingMap[$ca->booking->id] = [
                        'booking' => $ca->booking,
                        'role' => $ca->role,
                        'isLead' => $ca->is_lead,
                        'notes' => $ca->notes,
                    ];
                }
            }

            $uniqueJobs = array_values($allBookingMap);

            $customersServed = [];
            $totalRevenue = 0;
            $totalPhotos = 0;

            foreach ($uniqueJobs as $item) {
                $b = $item['booking'];
                $totalRevenue += ($b->approved_price ?: $b->total_price);
                $pCount = $b->jobReport && $b->jobReport->photos ? $b->jobReport->photos->count() : 0;
                $totalPhotos += $pCount;

                $otherCrew = [];
                if ($b->crew) {
                    foreach ($b->crew as $c) {
                        if ($c->employee_id !== $emp->id && $c->employee) {
                            $otherCrew[] = [
                                'name' => $c->employee->name,
                                'code' => $c->employee->employee_code,
                                'role' => $c->role,
                            ];
                        }
                    }
                }

                $customersServed[] = [
                    'bookingId' => $b->id,
                    'bookingNumber' => $b->booking_number,
                    'customerName' => $b->customer_name,
                    'customerEmail' => $b->customer_email,
                    'customerPhone' => $b->customer_phone,
                    'serviceName' => $b->service_name,
                    'serviceAddress' => $b->service_address,
                    'scheduledDate' => $b->scheduled_date,
                    'timeSlot' => $b->time_slot,
                    'status' => $b->status,
                    'totalPrice' => $b->approved_price ?: $b->total_price,
                    'depositPaid' => $b->deposit_paid,
                    'technicianRole' => $item['role'],
                    'isLead' => $item['isLead'],
                    'crewSize' => $b->crew ? max(1, $b->crew->count()) : 1,
                    'coWorkers' => $otherCrew,
                    'hasPhotos' => $pCount > 0,
                    'photosCount' => $pCount,
                    'faultNotes' => $b->jobReport?->fault_notes,
                ];
            }

            $technicianReports[] = [
                'employeeId' => $emp->id,
                'employeeCode' => $emp->employee_code,
                'name' => $emp->name,
                'email' => $emp->email,
                'phone' => $emp->phone,
                'designation' => $emp->designation,
                'department' => $emp->department,
                'status' => $emp->status,
                'hourlyRate' => $emp->hourly_rate,
                'licenseNumber' => $emp->license_number,
                'totalJobsCount' => count($uniqueJobs),
                'totalRevenueHandled' => $totalRevenue,
                'totalPhotosUploaded' => $totalPhotos,
                'customersServed' => $customersServed,
            ];
        }

        // 2. Fetch Customers
        $custQuery = Customer::with([
            'bookings.assignedEmployee',
            'bookings.crew.employee',
            'bookings.jobReport.photos',
        ]);

        if ($customerId) {
            $custQuery->where('id', $customerId);
        }

        $customers = $custQuery->orderBy('name', 'asc')->get();

        $customerReports = [];
        foreach ($customers as $cust) {
            $visits = [];
            $distinctTechMap = [];

            foreach ($cust->bookings as $b) {
                $crewList = [];

                if ($b->crew && $b->crew->count() > 0) {
                    foreach ($b->crew as $c) {
                        if ($c->employee) {
                            $tech = [
                                'employeeId' => $c->employee->id,
                                'employeeCode' => $c->employee->employee_code,
                                'name' => $c->employee->name,
                                'phone' => $c->employee->phone,
                                'designation' => $c->employee->designation,
                                'roleInJob' => $c->role,
                                'isLead' => $c->is_lead,
                            ];
                            $crewList[] = $tech;
                            $distinctTechMap[$c->employee->id] = $tech;
                        }
                    }
                } elseif ($b->assignedEmployee) {
                    $tech = [
                        'employeeId' => $b->assignedEmployee->id,
                        'employeeCode' => $b->assignedEmployee->employee_code,
                        'name' => $b->assignedEmployee->name,
                        'phone' => $b->assignedEmployee->phone,
                        'designation' => $b->assignedEmployee->designation,
                        'roleInJob' => 'Lead Technician',
                        'isLead' => true,
                    ];
                    $crewList[] = $tech;
                    $distinctTechMap[$b->assignedEmployee->id] = $tech;
                }

                $visits[] = [
                    'bookingId' => $b->id,
                    'bookingNumber' => $b->booking_number,
                    'serviceName' => $b->service_name,
                    'serviceAddress' => $b->service_address,
                    'scheduledDate' => $b->scheduled_date,
                    'timeSlot' => $b->time_slot,
                    'status' => $b->status,
                    'totalPrice' => $b->approved_price ?: $b->total_price,
                    'depositPaid' => $b->deposit_paid,
                    'balanceDue' => $b->balance_due,
                    'paymentStatus' => $b->payment_status,
                    'crewSize' => count($crewList),
                    'techniciansAssigned' => $crewList,
                    'jobReport' => $b->jobReport ? [
                        'status' => $b->jobReport->status,
                        'faultNotes' => $b->jobReport->fault_notes,
                        'treatmentApplied' => $b->jobReport->treatment_applied,
                        'photosCount' => $b->jobReport->photos ? $b->jobReport->photos->count() : 0,
                        'photos' => $b->jobReport->photos ?: [],
                    ] : null,
                ];
            }

            $customerReports[] = [
                'customerId' => $cust->id,
                'name' => $cust->name,
                'email' => $cust->email,
                'phone' => $cust->phone,
                'address' => $cust->address,
                'suburb' => $cust->suburb,
                'totalBookings' => $cust->bookings->count(),
                'totalTechniciansVisited' => count($distinctTechMap),
                'distinctTechnicians' => array_values($distinctTechMap),
                'serviceVisits' => $visits,
            ];
        }

        // 3. Multi-Crew Stats
        $allBookings = Booking::with('crew')->get();
        $singleTechJobs = $allBookings->filter(fn($b) => ($b->crew ? $b->crew->count() : 0) <= 1)->count();
        $multiTechJobs = $allBookings->filter(fn($b) => ($b->crew ? $b->crew->count() : 0) > 1)->count();
        $largeCrewJobs = $allBookings->filter(fn($b) => ($b->crew ? $b->crew->count() : 0) >= 3)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'technicianReports' => $technicianReports,
                'customerReports' => $customerReports,
                'stats' => [
                    'totalTechnicians' => $employees->count(),
                    'totalCustomers' => $customers->count(),
                    'singleTechJobs' => $singleTechJobs,
                    'multiTechJobs' => $multiTechJobs,
                    'largeCrewJobs' => $largeCrewJobs,
                ],
            ],
        ]);
    }
}
