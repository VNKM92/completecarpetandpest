<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Customer;
use App\Models\Employee;
use App\Services\ActivityLogger;
use App\Services\NotificationService;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $cleanEmail = strtolower(trim($request->email));

        $user = User::where('email', $cleanEmail)
            ->with(['role.permissions.permission', 'customer', 'employee'])
            ->first();

        if (!$user || $user->status !== 'ACTIVE' || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials or account is suspended',
            ], 401);
        }

        // Generate Sanctum plain text token
        $token = $user->createToken('auth-token')->plainTextToken;

        $permissions = [];
        if ($user->role && $user->role->permissions) {
            foreach ($user->role->permissions as $rp) {
                if ($rp->permission) {
                    $permissions[] = $rp->permission->slug;
                }
            }
        }

        $roleSlug = $user->role ? $user->role->slug : 'customer';
        $roleName = $user->role ? $user->role->name : 'Customer';

        ActivityLogger::log(
            action: 'LOGIN',
            module: 'Auth',
            entityId: (string) $user->id,
            details: ['email' => $user->email, 'role' => $roleName, 'roleSlug' => $roleSlug],
            userId: $user->id,
            userName: $user->name,
            request: $request
        );

        $userData = [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?: ($user->customer?->phone ?: $user->employee?->phone),
            'role' => $roleName,
            'roleSlug' => $roleSlug,
            'avatar' => $user->avatar,
            'permissions' => $permissions,
            'customerId' => $user->customer?->id,
            'employeeId' => $user->employee?->id,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Logged in successfully',
            'data' => [
                'token' => $token,
                'user' => $userData,
            ],
        ]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
        ]);

        $cleanEmail = strtolower(trim($request->email));

        if (User::where('email', $cleanEmail)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'An account with this email already exists. Please log in.',
            ], 409);
        }

        $roleType = $request->roleType === 'employee' ? 'staff' : 'customer';
        $role = Role::firstOrCreate(
            ['slug' => $roleType],
            [
                'name' => $roleType === 'customer' ? 'Customer' : 'Staff',
                'description' => $roleType === 'customer' ? 'Registered customer' : 'Field technician / staff',
                'is_system' => true,
            ]
        );

        $user = User::create([
            'name' => $request->name,
            'email' => $cleanEmail,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => 'ACTIVE',
            'role_id' => $role->id,
        ]);

        $customerId = null;
        if ($roleType === 'customer') {
            $customer = Customer::updateOrCreate(
                ['email' => $cleanEmail],
                [
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'suburb' => $request->suburb,
                    'postcode' => $request->postcode,
                ]
            );
            $customerId = $customer->id;
        } elseif ($roleType === 'staff') {
            $count = Employee::count();
            $code = 'EMP-' . str_pad($count + 101, 3, '0', STR_PAD_LEFT);
            Employee::create([
                'employee_code' => $code,
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $cleanEmail,
                'phone' => $request->phone ?: '0400000000',
                'designation' => 'Cleaning & Pest Field Specialist',
                'department' => 'Cleaning & Pest Control',
                'address' => $request->address ? ($request->address . ', ' . ($request->suburb ?: '') . ' ' . ($request->postcode ?: '')) : null,
            ]);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        NotificationService::create(
            title: 'Welcome to Brisbane Carpet & Pest Experts!',
            message: 'Your account is ready. Request quotations, track confirmed bookings, and view live technician inspection photos.',
            type: 'INFO',
            roleTarget: strtoupper($roleType),
            userId: $user->id,
            link: '/dashboard'
        );

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Auth',
            entityId: (string) $user->id,
            details: ['email' => $cleanEmail, 'role' => $role->slug],
            userId: $user->id,
            userName: $user->name,
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Account registered successfully',
            'data' => [
                'token' => $token,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $role->name,
                    'roleSlug' => $role->slug,
                    'customerId' => $customerId,
                ],
            ],
        ], 201);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user->load(['role.permissions.permission', 'customer', 'employee']);

        $permissions = [];
        if ($user->role && $user->role->permissions) {
            foreach ($user->role->permissions as $rp) {
                if ($rp->permission) {
                    $permissions[] = $rp->permission->slug;
                }
            }
        }

        $roleSlug = $user->role ? $user->role->slug : 'customer';
        $roleName = $user->role ? $user->role->name : 'Customer';

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'userId' => (string) $user->id,
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone ?: ($user->customer?->phone ?: $user->employee?->phone),
                    'role' => $roleName,
                    'roleSlug' => $roleSlug,
                    'avatar' => $user->avatar,
                    'permissions' => $permissions,
                    'customerId' => $user->customer?->id,
                    'employeeId' => $user->employee?->id,
                ],
            ],
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }
}
