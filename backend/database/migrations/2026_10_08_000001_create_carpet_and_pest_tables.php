<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. ROLES & PERMISSIONS
        Schema::create('roles', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('role_id');
            $table->string('permission_id');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            $table->unique(['role_id', 'permission_id']);
        });

        // Add role_id, phone, avatar, status to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('avatar')->nullable()->after('phone');
            $table->string('status')->default('ACTIVE')->after('avatar');
            $table->string('role_id')->nullable()->after('status');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
        });

        // 2. CUSTOMERS
        Schema::create('customers', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('suburb')->nullable();
            $table->string('postcode')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 3. ENQUIRIES
        Schema::create('enquiries', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('enquiry_number')->nullable()->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('service')->nullable();
            $table->string('bedrooms')->nullable();
            $table->string('bathrooms')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('NEW');
            $table->boolean('is_read')->default(false);
            $table->text('notes')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        // 4. EMPLOYEES
        Schema::create('employees', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employee_code')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('designation');
            $table->string('department')->default('Cleaning & Pest Control');
            $table->string('status')->default('ACTIVE');
            $table->double('hourly_rate')->default(35.0);
            $table->text('skills')->nullable(); // JSON
            $table->string('emergency_contact')->nullable();
            $table->text('address')->nullable();
            $table->string('license_number')->nullable();
            $table->dateTime('join_date')->useCurrent();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 5. ATTENDANCE
        Schema::create('attendances', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('employee_id');
            $table->date('date');
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->string('status')->default('PRESENT');
            $table->text('work_notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unique(['employee_id', 'date']);
        });

        // 6. BOOKINGS
        Schema::create('bookings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('booking_number')->unique();
            $table->string('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('service_id')->nullable();
            $table->string('service_name');
            $table->text('service_address');
            $table->string('suburb')->nullable();
            $table->string('postcode')->nullable();
            $table->dateTime('scheduled_date')->nullable();
            $table->string('time_slot')->nullable();
            $table->integer('square_footage')->nullable();
            $table->integer('rooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->text('addons')->nullable(); // JSON
            $table->double('total_price')->default(0);
            $table->double('approved_price')->nullable();
            $table->double('deposit_required')->default(50.0);
            $table->double('deposit_paid')->default(0.0);
            $table->double('balance_due')->default(0.0);
            $table->string('quote_status')->default('PENDING_APPROVAL');
            $table->string('status')->default('PENDING');
            $table->string('payment_status')->default('UNPAID');
            $table->string('payment_gateway')->nullable();
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('reminder_sent_2days')->default(false);
            $table->dateTime('reminder_sent_date')->nullable();
            $table->boolean('reminder_mobile_opt')->default(true);
            $table->boolean('reminder_email_opt')->default(true);
            $table->string('assigned_employee_id')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->foreign('assigned_employee_id')->references('id')->on('employees')->onDelete('set null');
        });

        // 7. BOOKING CREW MEMBERS
        Schema::create('booking_crew_members', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('booking_id');
            $table->string('employee_id');
            $table->string('role')->default('Technician');
            $table->boolean('is_lead')->default(false);
            $table->dateTime('assigned_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unique(['booking_id', 'employee_id']);
        });

        // 8. ORDERS
        Schema::create('orders', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('order_number')->unique();
            $table->string('booking_id')->nullable()->unique();
            $table->string('customer_id')->nullable();
            $table->double('subtotal');
            $table->double('discount')->default(0);
            $table->double('tax')->default(0);
            $table->double('total_amount');
            $table->string('payment_status')->default('UNPAID');
            $table->string('payment_method')->nullable();
            $table->dateTime('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        // 9. INVOICES
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('invoice_number')->unique();
            $table->string('booking_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->double('subtotal')->default(0);
            $table->double('gst_rate')->default(10.0);
            $table->double('gst_amount')->default(0);
            $table->double('discount')->default(0);
            $table->double('total_amount')->default(0);
            $table->double('deposit_paid')->default(0);
            $table->double('balance_due')->default(0);
            $table->string('status')->default('UNPAID');
            $table->string('payment_method')->nullable();
            $table->dateTime('issued_date')->useCurrent();
            $table->dateTime('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('invoice_id');
            $table->text('description');
            $table->integer('quantity')->default(1);
            $table->double('unit_price')->default(0);
            $table->double('total_price')->default(0);
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('cascade');
        });

        // 10. PAYMENT TRANSACTIONS
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('transaction_number')->unique();
            $table->string('booking_id')->nullable();
            $table->string('invoice_id')->nullable();
            $table->string('customer_id')->nullable();
            $table->double('amount');
            $table->string('currency')->default('AUD');
            $table->string('payment_gateway');
            $table->string('payment_type')->default('DEPOSIT');
            $table->string('status')->default('SUCCESS');
            $table->string('gateway_ref')->nullable();
            $table->string('payer_email')->nullable();
            $table->text('notes')->nullable();
            $table->text('metadata')->nullable(); // JSON
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        // 11. JOB REPORTS & PHOTOS
        Schema::create('job_reports', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('booking_id')->unique();
            $table->string('employee_id')->nullable();
            $table->dateTime('visit_time')->nullable();
            $table->dateTime('completion_time')->nullable();
            $table->string('status')->default('SCHEDULED');
            $table->text('work_description')->nullable();
            $table->text('fault_notes')->nullable();
            $table->text('treatment_applied')->nullable();
            $table->text('checklist')->nullable(); // JSON
            $table->longText('customer_signature')->nullable();
            $table->text('summary_notes')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('set null');
        });

        Schema::create('job_photos', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('job_report_id');
            $table->text('photo_url');
            $table->string('type')->default('BEFORE');
            $table->text('caption')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();

            $table->foreign('job_report_id')->references('id')->on('job_reports')->onDelete('cascade');
        });

        // 12. REMINDERS & NOTIFICATIONS
        Schema::create('reminder_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('booking_id');
            $table->string('recipient_email');
            $table->string('recipient_phone')->nullable();
            $table->string('channel');
            $table->dateTime('scheduled_for');
            $table->dateTime('sent_at')->nullable();
            $table->string('status')->default('SENT');
            $table->text('error')->nullable();
            $table->timestamps();

            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('role_target')->nullable();
            $table->string('title');
            $table->text('message');
            $table->string('type')->default('INFO');
            $table->string('link')->nullable();
            $table->boolean('is_read')->default(false);
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // 13. SERVICES & CATEGORIES
        Schema::create('service_categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category_id')->nullable();
            $table->text('short_desc')->nullable();
            $table->longText('description')->nullable();
            $table->double('price_starting')->nullable();
            $table->string('price_unit')->default('Fixed');
            $table->string('duration')->nullable();
            $table->string('icon')->nullable();
            $table->string('hero_image')->nullable();
            $table->text('gallery')->nullable(); // JSON
            $table->text('features')->nullable(); // JSON
            $table->text('tab_data')->nullable(); // JSON
            $table->string('meta_title')->nullable();
            $table->text('meta_desc')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->string('schema_type')->default('CleaningService');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('service_categories')->onDelete('set null');
        });

        // 14. CMS PAGES, BLOGS, FAQS, TESTIMONIALS
        Schema::create('pages', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('heading')->nullable();
            $table->string('subheading')->nullable();
            $table->longText('content')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_desc')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->string('robots')->default('index, follow');
            $table->longText('custom_schema')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });

        Schema::create('blog_categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('blogs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->string('featured_img')->nullable();
            $table->string('author')->default('Brisbane Carpet & Pest Experts');
            $table->integer('read_time')->default(5);
            $table->string('category_id')->nullable();
            $table->text('tags')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_desc')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('og_image')->nullable();
            $table->string('status')->default('PUBLISHED');
            $table->dateTime('published_at')->useCurrent();
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('blog_categories')->onDelete('set null');
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->text('question');
            $table->longText('answer');
            $table->string('category_id')->nullable();
            $table->string('service_tag')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('faq_categories')->onDelete('set null');
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('client_name');
            $table->string('role')->default('Client');
            $table->string('location')->default('Brisbane');
            $table->string('avatar')->nullable();
            $table->integer('rating')->default(5);
            $table->text('review');
            $table->string('source')->default('Google');
            $table->boolean('is_approved')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // 15. MEDIA, LOGS, SETTINGS
        Schema::create('media', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('filename');
            $table->text('url');
            $table->string('mime_type')->nullable();
            $table->integer('size')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('folder')->default('general');
            $table->timestamps();
        });

        Schema::create('email_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('recipient');
            $table->string('subject');
            $table->longText('body')->nullable();
            $table->string('status')->default('SENT');
            $table->text('error')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('phone');
            $table->text('message');
            $table->string('direction')->default('OUTBOUND');
            $table->string('status')->default('SENT');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('action');
            $table->string('module');
            $table->string('entity_id')->nullable();
            $table->text('details')->nullable(); // JSON
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('key')->unique();
            $table->longText('value');
            $table->string('group')->default('general');
            $table->string('label')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('whatsapp_logs');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('media');
        Schema::dropIfExists('testimonials');
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('blogs');
        Schema::dropIfExists('blog_categories');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('services');
        Schema::dropIfExists('service_categories');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('reminder_logs');
        Schema::dropIfExists('job_photos');
        Schema::dropIfExists('job_reports');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('booking_crew_members');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('enquiries');
        Schema::dropIfExists('customers');
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn(['phone', 'avatar', 'status', 'role_id']);
        });
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
