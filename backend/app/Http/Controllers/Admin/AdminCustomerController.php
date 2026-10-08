<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customer;
use App\Services\ActivityLogger;

class AdminCustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Customer::with(['bookings', 'invoices', 'payments', 'user']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('suburb', 'like', "%{$search}%");
            });
        }

        $customers = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['success' => true, 'data' => $customers]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:customers,email',
        ]);

        $customer = Customer::create([
            'name' => $request->name,
            'email' => strtolower(trim($request->email)),
            'phone' => $request->phone,
            'address' => $request->address,
            'suburb' => $request->suburb,
            'postcode' => $request->postcode,
            'notes' => $request->notes,
        ]);

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Customers',
            entityId: (string) $customer->id,
            details: ['name' => $request->name, 'email' => $request->email],
            request: $request
        );

        return response()->json(['success' => true, 'data' => $customer], 201);
    }

    public function update(Request $request, $id = null)
    {
        $customerId = $id ?: $request->input('id');
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $customer->update($request->only(['name', 'phone', 'address', 'suburb', 'postcode', 'notes']));

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Customers',
            entityId: (string) $customer->id,
            details: $request->all(),
            request: $request
        );

        return response()->json(['success' => true, 'data' => $customer]);
    }

    public function destroy(Request $request, $id = null)
    {
        $customerId = $id ?: $request->query('id', $request->input('id'));
        $customer = Customer::find($customerId);
        if (!$customer) {
            return response()->json(['success' => false, 'message' => 'Customer not found'], 404);
        }

        $customer->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Customers',
            entityId: (string) $customerId,
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Customer deleted']);
    }
}
