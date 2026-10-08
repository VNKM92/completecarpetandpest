<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\CustomerPortalController;
use App\Http\Controllers\EmployeePortalController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminQuotationController;
use App\Http\Controllers\Admin\AdminInvoiceController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminEmployeeController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminAssignmentController;
use App\Http\Controllers\Admin\AdminRoleController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminEnquiryController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminBlogController;
use App\Http\Controllers\Admin\AdminFaqController;
use App\Http\Controllers\Admin\AdminPageController;
use App\Http\Controllers\Admin\AdminTestimonialController;
use App\Http\Controllers\Admin\AdminMediaController;
use App\Http\Controllers\Admin\AdminPaymentSettingController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminActivityLogController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminEmailController;
use App\Http\Controllers\Admin\AdminWhatsAppController;
use App\Http\Controllers\Admin\AdminHomepageController;
use App\Http\Controllers\Admin\AdminCommunicationSettingController;
use App\Http\Controllers\Admin\AdminSettingController;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/
Route::get('/services', [PublicController::class, 'services']);
Route::get('/blogs', [PublicController::class, 'blogs']);
Route::get('/faqs', [PublicController::class, 'faqs']);
Route::get('/testimonials', [PublicController::class, 'testimonials']);
Route::get('/settings', [PublicController::class, 'settings']);
Route::post('/contact', [PublicController::class, 'submitContact']);
Route::post('/bookings', [PublicController::class, 'submitBooking']);
Route::match(['get', 'post'], '/cron/reminders', [PublicController::class, 'cronReminders']);

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

/*
|--------------------------------------------------------------------------
| Customer Portal Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('customer')->middleware('auth:sanctum')->group(function () {
    Route::get('/quotations', [CustomerPortalController::class, 'quotations']);
    Route::get('/invoices', [CustomerPortalController::class, 'invoices']);
    Route::get('/payments', [CustomerPortalController::class, 'payments']);
    Route::get('/notifications', [CustomerPortalController::class, 'notifications']);
    Route::patch('/notifications', [CustomerPortalController::class, 'markNotificationsRead']);
});

/*
|--------------------------------------------------------------------------
| Employee / Field Technician Portal Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('employee')->middleware('auth:sanctum')->group(function () {
    Route::get('/jobs', [EmployeePortalController::class, 'jobs']);
    Route::post('/jobs/{id}/upload', [EmployeePortalController::class, 'uploadJobData']);
});

/*
|--------------------------------------------------------------------------
| Admin Panel API Routes (Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {
    // 1. Dashboard KPIs
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // 2. Bookings
    Route::get('/bookings', [AdminBookingController::class, 'index']);
    Route::post('/bookings', [AdminBookingController::class, 'store']);
    Route::patch('/bookings', [AdminBookingController::class, 'update']);
    Route::patch('/bookings/{id}', [AdminBookingController::class, 'update']);
    Route::delete('/bookings', [AdminBookingController::class, 'destroy']);
    Route::delete('/bookings/{id}', [AdminBookingController::class, 'destroy']);

    // 3. Quotations
    Route::get('/quotations', [AdminQuotationController::class, 'index']);
    Route::get('/quotations/{id}', [AdminQuotationController::class, 'show']);
    Route::patch('/quotations/{id}', [AdminQuotationController::class, 'update']);
    Route::delete('/quotations/{id}', [AdminQuotationController::class, 'destroy']);

    // 4. Invoices
    Route::get('/invoices', [AdminInvoiceController::class, 'index']);
    Route::post('/invoices', [AdminInvoiceController::class, 'store']);
    Route::patch('/invoices', [AdminInvoiceController::class, 'update']);
    Route::patch('/invoices/{id}', [AdminInvoiceController::class, 'update']);
    Route::delete('/invoices', [AdminInvoiceController::class, 'destroy']);
    Route::delete('/invoices/{id}', [AdminInvoiceController::class, 'destroy']);

    // 5. Customers (CRM)
    Route::get('/customers', [AdminCustomerController::class, 'index']);
    Route::post('/customers', [AdminCustomerController::class, 'store']);
    Route::patch('/customers', [AdminCustomerController::class, 'update']);
    Route::patch('/customers/{id}', [AdminCustomerController::class, 'update']);
    Route::delete('/customers', [AdminCustomerController::class, 'destroy']);
    Route::delete('/customers/{id}', [AdminCustomerController::class, 'destroy']);

    // 6. HRM & Employees
    Route::get('/hrm/employees', [AdminEmployeeController::class, 'index']);
    Route::post('/hrm/employees', [AdminEmployeeController::class, 'store']);
    Route::patch('/hrm/employees', [AdminEmployeeController::class, 'update']);
    Route::patch('/hrm/employees/{id}', [AdminEmployeeController::class, 'update']);
    Route::delete('/hrm/employees', [AdminEmployeeController::class, 'destroy']);
    Route::delete('/hrm/employees/{id}', [AdminEmployeeController::class, 'destroy']);

    // 7. Attendance
    Route::get('/hrm/attendance', [AdminAttendanceController::class, 'index']);
    Route::post('/hrm/attendance', [AdminAttendanceController::class, 'store']);

    // 8. Assignments & Crew Dispatch
    Route::get('/assignments', [AdminAssignmentController::class, 'index']);
    Route::post('/assignments', [AdminAssignmentController::class, 'store']);

    // 9. Roles & Permissions (RBAC)
    Route::get('/roles', [AdminRoleController::class, 'index']);
    Route::post('/roles', [AdminRoleController::class, 'store']);
    Route::patch('/roles', [AdminRoleController::class, 'update']);
    Route::patch('/roles/{id}', [AdminRoleController::class, 'update']);
    Route::delete('/roles', [AdminRoleController::class, 'destroy']);
    Route::delete('/roles/{id}', [AdminRoleController::class, 'destroy']);

    // 10. Users
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::patch('/users', [AdminUserController::class, 'update']);
    Route::patch('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users', [AdminUserController::class, 'destroy']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    // 11. Enquiries
    Route::get('/enquiries', [AdminEnquiryController::class, 'index']);
    Route::patch('/enquiries', [AdminEnquiryController::class, 'update']);
    Route::patch('/enquiries/{id}', [AdminEnquiryController::class, 'update']);
    Route::delete('/enquiries', [AdminEnquiryController::class, 'destroy']);
    Route::delete('/enquiries/{id}', [AdminEnquiryController::class, 'destroy']);

    // 12. Services
    Route::get('/services', [AdminServiceController::class, 'index']);
    Route::post('/services', [AdminServiceController::class, 'store']);
    Route::patch('/services', [AdminServiceController::class, 'update']);
    Route::patch('/services/{id}', [AdminServiceController::class, 'update']);
    Route::delete('/services', [AdminServiceController::class, 'destroy']);
    Route::delete('/services/{id}', [AdminServiceController::class, 'destroy']);

    // 13. Blogs
    Route::get('/blogs', [AdminBlogController::class, 'index']);
    Route::post('/blogs', [AdminBlogController::class, 'store']);
    Route::patch('/blogs', [AdminBlogController::class, 'update']);
    Route::patch('/blogs/{id}', [AdminBlogController::class, 'update']);
    Route::delete('/blogs', [AdminBlogController::class, 'destroy']);
    Route::delete('/blogs/{id}', [AdminBlogController::class, 'destroy']);

    // 14. FAQs
    Route::get('/faqs', [AdminFaqController::class, 'index']);
    Route::post('/faqs', [AdminFaqController::class, 'store']);
    Route::patch('/faqs', [AdminFaqController::class, 'update']);
    Route::patch('/faqs/{id}', [AdminFaqController::class, 'update']);
    Route::delete('/faqs', [AdminFaqController::class, 'destroy']);
    Route::delete('/faqs/{id}', [AdminFaqController::class, 'destroy']);

    // 15. Pages (CMS)
    Route::get('/pages', [AdminPageController::class, 'index']);
    Route::post('/pages', [AdminPageController::class, 'store']);
    Route::patch('/pages', [AdminPageController::class, 'update']);
    Route::patch('/pages/{id}', [AdminPageController::class, 'update']);
    Route::delete('/pages', [AdminPageController::class, 'destroy']);
    Route::delete('/pages/{id}', [AdminPageController::class, 'destroy']);

    // 16. Testimonials
    Route::get('/testimonials', [AdminTestimonialController::class, 'index']);
    Route::post('/testimonials', [AdminTestimonialController::class, 'store']);
    Route::patch('/testimonials', [AdminTestimonialController::class, 'update']);
    Route::patch('/testimonials/{id}', [AdminTestimonialController::class, 'update']);
    Route::delete('/testimonials', [AdminTestimonialController::class, 'destroy']);
    Route::delete('/testimonials/{id}', [AdminTestimonialController::class, 'destroy']);

    // 17. Media & Uploads
    Route::get('/media', [AdminMediaController::class, 'index']);
    Route::post('/media', [AdminMediaController::class, 'store']);
    Route::post('/upload', [AdminMediaController::class, 'upload']);
    Route::delete('/media', [AdminMediaController::class, 'destroy']);
    Route::delete('/media/{id}', [AdminMediaController::class, 'destroy']);

    // 18. Payment Settings
    Route::get('/payment-settings', [AdminPaymentSettingController::class, 'index']);
    Route::post('/payment-settings', [AdminPaymentSettingController::class, 'store']);

    // 19. Orders
    Route::get('/orders', [AdminOrderController::class, 'index']);
    Route::patch('/orders', [AdminOrderController::class, 'update']);
    Route::patch('/orders/{id}', [AdminOrderController::class, 'update']);

    // 20. Reports
    Route::get('/reports/technicians', [AdminReportController::class, 'technicians']);

    // 21. Activity Logs
    Route::get('/activity-logs', [AdminActivityLogController::class, 'index']);

    // 22. Notifications
    Route::get('/notifications', [AdminNotificationController::class, 'index']);
    Route::patch('/notifications', [AdminNotificationController::class, 'update']);

    // 23. Email Logs & Test
    Route::get('/emails', [AdminEmailController::class, 'index']);
    Route::post('/emails', [AdminEmailController::class, 'store']);

    // 24. WhatsApp Logs & Test
    Route::get('/whatsapp', [AdminWhatsAppController::class, 'index']);
    Route::post('/whatsapp', [AdminWhatsAppController::class, 'store']);

    // 25. Homepage Dynamic CMS
    Route::get('/homepage', [AdminHomepageController::class, 'index']);
    Route::post('/homepage', [AdminHomepageController::class, 'store']);

    // 26. Communication Settings & Test Dispatch
    Route::get('/communication-settings', [AdminCommunicationSettingController::class, 'index']);
    Route::post('/communication-settings', [AdminCommunicationSettingController::class, 'store']);
    Route::match(['put', 'post'], '/communication-settings/test', [AdminCommunicationSettingController::class, 'test']);
    Route::put('/communication-settings', [AdminCommunicationSettingController::class, 'test']);

    // 27. Site Settings
    Route::get('/settings', [AdminSettingController::class, 'index']);
    Route::post('/settings', [AdminSettingController::class, 'store']);
});
