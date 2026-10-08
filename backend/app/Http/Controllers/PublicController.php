<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Blog;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\SiteSetting;
use App\Models\Enquiry;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\ReminderLog;
use App\Services\NotificationService;
use App\Services\ActivityLogger;

class PublicController extends Controller
{
    public function services(Request $request)
    {
        $slug = $request->query('slug');

        if ($slug) {
            $service = Service::where('slug', $slug)
                ->where('is_active', true)
                ->with('category')
                ->first();

            if (!$service) {
                return response()->json(['success' => false, 'message' => 'Service not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $service]);
        }

        $services = Service::where('is_active', true)
            ->with('category')
            ->orderBy('order', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $services]);
    }

    public function blogs(Request $request)
    {
        $slug = $request->query('slug');

        if ($slug) {
            $blog = Blog::where('slug', $slug)
                ->where('status', 'PUBLISHED')
                ->with('category')
                ->first();

            if (!$blog) {
                return response()->json(['success' => false, 'message' => 'Blog article not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $blog]);
        }

        $blogs = Blog::where('status', 'PUBLISHED')
            ->with('category')
            ->orderBy('published_at', 'desc')
            ->get();

        return response()->json(['success' => true, 'data' => $blogs]);
    }

    public function faqs(Request $request)
    {
        $tag = $request->query('serviceTag');

        $query = Faq::where('is_active', true)->with('category');
        if ($tag) {
            $query->where('service_tag', $tag);
        }

        $faqs = $query->orderBy('order', 'asc')->get();

        return response()->json(['success' => true, 'data' => $faqs]);
    }

    public function testimonials(Request $request)
    {
        $testimonials = Testimonial::where('is_approved', true)
            ->orderBy('order', 'asc')
            ->get();

        return response()->json(['success' => true, 'data' => $testimonials]);
    }

    public function settings(Request $request)
    {
        $settings = SiteSetting::all();
        $map = [];
        foreach ($settings as $s) {
            $map[$s->key] = $s->value;
        }

        return response()->json(['success' => true, 'data' => $map]);
    }

    public function submitContact(Request $request)
    {
        $request->validate([
            'firstName' => 'required|string',
            'email' => 'required|email',
            'phone' => 'required|string',
        ]);

        $count = Enquiry::count();
        $enquiryNumber = 'ENQ-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $enquiry = Enquiry::create([
            'enquiry_number' => $enquiryNumber,
            'first_name' => $request->firstName,
            'last_name' => $request->lastName ?: '',
            'email' => $request->email,
            'phone' => $request->phone,
            'service' => $request->service,
            'bedrooms' => $request->bedrooms,
            'bathrooms' => $request->bathrooms,
            'message' => $request->message,
            'status' => 'NEW',
            'ip_address' => $request->ip(),
        ]);

        NotificationService::create(
            title: "New Enquiry #{$enquiryNumber}",
            message: "From {$request->firstName} ({$request->email}) for {$request->service}",
            type: 'INFO',
            roleTarget: 'ADMIN',
            link: '/admin/enquiries'
        );

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Enquiries',
            entityId: (string) $enquiry->id,
            details: ['enquiryNumber' => $enquiryNumber, 'email' => $request->email],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Enquiry submitted successfully',
            'enquiryNumber' => $enquiryNumber,
            'data' => $enquiry,
        ], 201);
    }

    public function submitBooking(Request $request)
    {
        $request->validate([
            'customerName' => 'required|string',
            'customerEmail' => 'required|email',
            'customerPhone' => 'required|string',
            'serviceName' => 'required|string',
            'serviceAddress' => 'required|string',
        ]);

        $count = Booking::count();
        $bookingNumber = 'BK-' . date('Y') . '-' . str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        $customer = Customer::firstOrCreate(
            ['email' => $request->customerEmail],
            [
                'name' => $request->customerName,
                'phone' => $request->customerPhone,
                'address' => $request->serviceAddress,
                'suburb' => $request->suburb,
                'postcode' => $request->postcode,
            ]
        );

        $totalPrice = floatval($request->totalPrice ?: 0);
        $depositRequired = floatval($request->depositRequired ?: 50.0);

        $booking = Booking::create([
            'booking_number' => $bookingNumber,
            'customer_id' => $customer->id,
            'customer_name' => $request->customerName,
            'customer_email' => $request->customerEmail,
            'customer_phone' => $request->customerPhone,
            'service_id' => $request->serviceId,
            'service_name' => $request->serviceName,
            'service_address' => $request->serviceAddress,
            'suburb' => $request->suburb,
            'postcode' => $request->postcode,
            'scheduled_date' => $request->scheduledDate ? date('Y-m-d H:i:s', strtotime($request->scheduledDate)) : null,
            'time_slot' => $request->timeSlot ?: 'Morning (8AM - 11AM)',
            'square_footage' => $request->squareFootage,
            'rooms' => $request->rooms,
            'bathrooms' => $request->bathrooms,
            'addons' => is_array($request->addons) ? json_encode($request->addons) : $request->addons,
            'total_price' => $totalPrice,
            'deposit_required' => $depositRequired,
            'balance_due' => $totalPrice,
            'quote_status' => 'PENDING_APPROVAL',
            'status' => 'PENDING',
            'payment_status' => 'UNPAID',
            'notes' => $request->notes,
        ]);

        NotificationService::create(
            title: "New Booking Request #{$bookingNumber}",
            message: "{$request->customerName} requested {$request->serviceName} for \${$totalPrice} AUD",
            type: 'INFO',
            roleTarget: 'ADMIN',
            link: '/admin/bookings'
        );

        ActivityLogger::log(
            action: 'CREATE',
            module: 'Bookings',
            entityId: (string) $booking->id,
            details: ['bookingNumber' => $bookingNumber, 'totalPrice' => $totalPrice],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Booking request submitted successfully',
            'bookingNumber' => $bookingNumber,
            'data' => $booking,
        ], 201);
    }

    public function cronReminders(Request $request)
    {
        $now = now();
        $startWindow = $now->copy()->addHours(24);
        $endWindow = $now->copy()->addHours(72);

        $eligibleBookings = Booking::whereBetween('scheduled_date', [$startWindow, $endWindow])
            ->where('reminder_sent_2days', false)
            ->whereIn('status', ['CONFIRMED', 'PENDING'])
            ->with(['customer', 'assignedEmployee'])
            ->get();

        $results = [];

        foreach ($eligibleBookings as $booking) {
            $booking->update([
                'reminder_sent_2days' => true,
                'reminder_sent_date' => now(),
            ]);

            ReminderLog::create([
                'booking_id' => $booking->id,
                'recipient_email' => $booking->customer_email,
                'recipient_phone' => $booking->customer_phone,
                'channel' => 'EMAIL',
                'scheduled_for' => $booking->scheduled_date,
                'sent_at' => now(),
                'status' => 'SENT',
            ]);

            $results[] = [
                'bookingNumber' => $booking->booking_number,
                'customer' => $booking->customer_name,
                'email' => $booking->customer_email,
                'phone' => $booking->customer_phone,
                'scheduledDate' => $booking->scheduled_date,
                'status' => 'SUCCESS',
            ];
        }

        return response()->json([
            'success' => true,
            'timestamp' => now()->toIso8601String(),
            'processedCount' => count($results),
            'data' => $results,
        ]);
    }
}
