<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Services\ActivityLogger;

class AdminRoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::with(['permissions.permission', 'users'])->get();
        $permissions = Permission::orderBy('category')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'roles' => $roles,
                'permissions' => $permissions,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'slug' => 'required|string|unique:roles,slug',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => strtolower(trim($request->slug)),
            'description' => $request->description,
            'is_system' => false,
        ]);

        if ($request->has('permissions') && is_array($request->permissions)) {
            foreach ($request->permissions as $permId) {
                RolePermission::firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permId,
                ]);
            }
        }

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Roles',
            entityId: (string) $role->id,
            details: ['name' => $request->name, 'slug' => $request->slug],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $role->fresh('permissions.permission')], 201);
    }

    public function update(Request $request, $id = null)
    {
        $roleId = $id ?: $request->input('id');
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        if ($request->has('name')) $role->name = $request->name;
        if ($request->has('description')) $role->description = $request->description;
        $role->save();

        if ($request->has('permissions') && is_array($request->permissions)) {
            RolePermission::where('role_id', $role->id)->delete();
            foreach ($request->permissions as $permId) {
                RolePermission::firstOrCreate([
                    'role_id' => $role->id,
                    'permission_id' => $permId,
                ]);
            }
        }

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Roles',
            entityId: (string) $role->id,
            details: ['name' => $role->name],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $role->fresh('permissions.permission')]);
    }

    public function destroy(Request $request, $id = null)
    {
        $roleId = $id ?: $request->query('id', $request->input('id'));
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Role not found'], 404);
        }

        if ($role->is_system) {
            return response()->json(['success' => false, 'message' => 'System roles cannot be deleted'], 403);
        }

        $role->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Roles',
            entityId: (string) $roleId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Role deleted successfully']);
    }
}
