<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\ActivityLogger;
use App\Services\NotificationService;

class AdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Invoice::with(['items', 'booking', 'payments']);

        if ($status && $status !== 'ALL') {
            $query->where('status', $status);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%");
            });
        }

        $invoices = $query->orderBy('issued_date', 'desc')->get();

        $totalRevenue = $invoices->where('status', 'PAID')->sum('total_amount');
        $totalPending = $invoices->where('status', '!=', 'PAID')->sum('balance_due');

        return response()->json([
            'success' => true,
            'data' => [
                'invoices' => $invoices,
                'stats' => [
                    'totalCount' => $invoices->count(),
                    'totalRevenue' => floatval($totalRevenue),
                    'totalPending' => floatval($totalPending),
                ],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string',
            'customerEmail' => 'required|email',
            'items' => 'required|array|min:1',
        ]);

        $calculatedSubtotal = 0;
        foreach ($request->items as $item) {
            $qty = intval($item['quantity'] ?? 1);
            $unit = floatval($item['unitPrice'] ?? 0);
            $calculatedSubtotal += ($qty * $unit);
        }

        $discount = floatval($request->discount ?? 0);
        $taxableSubtotal = max(0, $calculatedSubtotal - $discount);
        $gstRate = 10.0; // Australia GST 10%
        $gstAmount = $taxableSubtotal * 0.1;
        $totalAmount = $taxableSubtotal + $gstAmount;

        $year = date('Y');
        $count = Invoice::count();
        $invoiceNumber = 'INV-' . $year . '-' . str_pad($count + 1001, 4, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'booking_id' => $request->bookingId,
            'customer_id' => $request->customerId,
            'customer_name' => $request->customerName,
            'customer_email' => $request->customerEmail,
            'customer_phone' => $request->customerPhone,
            'customer_address' => $request->customerAddress,
            'subtotal' => $calculatedSubtotal,
            'gst_rate' => $gstRate,
            'gst_amount' => $gstAmount,
            'discount' => $discount,
            'total_amount' => $totalAmount,
            'deposit_paid' => 0,
            'balance_due' => $totalAmount,
            'status' => 'SENT',
            'payment_method' => $request->paymentMethod ?: 'INVOICE',
            'issued_date' => now(),
            'due_date' => $request->dueDate ? date('Y-m-d H:i:s', strtotime($request->dueDate)) : now()->addDays(7),
            'notes' => $request->notes ?: 'Thank you for choosing Brisbane Carpet & Pest Experts!',
        ]);

        foreach ($request->items as $item) {
            $qty = intval($item['quantity'] ?? 1);
            $unit = floatval($item['unitPrice'] ?? 0);
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $qty,
                'unit_price' => $unit,
                'total_price' => $qty * $unit,
            ]);
        }

        NotificationService::create(
            title: "New Tax Invoice #{$invoiceNumber}",
            message: "A new tax invoice for \${$totalAmount} AUD has been issued.",
            type: 'INFO',
            roleTarget: 'CUSTOMER',
            link: '/dashboard?tab=invoices'
        );

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Invoices',
            entityId: (string) $invoice->id,
            details: ['invoiceNumber' => $invoiceNumber, 'totalAmount' => $totalAmount, 'email' => $request->customerEmail],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => "Invoice {$invoiceNumber} created and dispatched successfully.",
            'data' => $invoice->fresh('items'),
        ], 201);
    }

    public function update(Request $request, $id = null)
    {
        $invoiceId = $id ?: $request->input('id');
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $updateData = [];
        if ($request->has('status')) $updateData['status'] = $request->status;
        if ($request->has('notes')) $updateData['notes'] = $request->notes;
        if ($request->has('paymentMethod')) $updateData['payment_method'] = $request->paymentMethod;
        if ($request->has('depositPaid')) $updateData['deposit_paid'] = floatval($request->depositPaid);
        if ($request->has('balanceDue')) $updateData['balance_due'] = floatval($request->balanceDue);

        $invoice->update($updateData);

        ActivityLogger::log(
            action: 'UPDATE',
            module: 'Invoices',
            entityId: (string) $invoice->id,
            details: $updateData,
            request: $request
        );

        return response()->json(['success' => true, 'data' => $invoice->fresh(['items', 'booking'])]);
    }

    public function destroy(Request $request, $id = null)
    {
        $invoiceId = $id ?: $request->query('id', $request->input('id'));
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        $invoiceNumber = $invoice->invoice_number;
        $invoice->delete();

        ActivityLogger::log(
            action: 'DELETE',
            module: 'Invoices',
            entityId: (string) $invoiceId,
            details: ['invoiceNumber' => $invoiceNumber],
            request: $request
        );

        return response()->json(['success' => true, 'message' => 'Invoice deleted']);
    }
}
