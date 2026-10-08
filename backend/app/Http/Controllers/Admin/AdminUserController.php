<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Services\ActivityLogger;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $roleSlug = $request->query('role');

        $query = User::with(['role', 'customer', 'employee']);

        if ($roleSlug) {
            $query->whereHas('role', function ($q) use ($roleSlug) {
                $q->where('slug', $roleSlug);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->get();
        $roles = Role::all();

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
                'roles' => $roles,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'status' => $request->status ?: 'ACTIVE',
            'role_id' => $request->roleId,
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Users',
            entityId: (string) $user->id,
            details: ['name' => $user->name, 'email' => $user->email],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $user->fresh('role')], 201);
    }

    public function update(Request $request, $id = null)
    {
        $userId = $id ?: $request->input('id');
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $updateData = [];
        if ($request->has('name')) $updateData['name'] = $request->name;
        if ($request->has('phone')) $updateData['phone'] = $request->phone;
        if ($request->has('status')) $updateData['status'] = $request->status;
        if ($request->has('roleId')) $updateData['role_id'] = $request->roleId;
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Users',
            entityId: (string) $user->id,
            details: ['name' => $user->name, 'status' => $user->status],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $user->fresh('role')]);
    }

    public function destroy(Request $request, $id = null)
    {
        $userId = $id ?: $request->query('id', $request->input('id'));
        $user = User::find($userId);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Users',
            entityId: (string) $userId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'User deleted successfully']);
    }
}
