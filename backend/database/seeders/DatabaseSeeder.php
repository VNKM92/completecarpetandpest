<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\ServiceCategory;
use App\Models\Service;
use App\Models\BlogCategory;
use App\Models\Blog;
use App\Models\FaqCategory;
use App\Models\Faq;
use App\Models\Testimonial;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Booking;
use App\Models\Enquiry;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SiteSetting;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Permissions
        $permissionsList = [
            ['name' => 'Read Services', 'slug' => 'services:read', 'category' => 'Services'],
            ['name' => 'Create Services', 'slug' => 'services:create', 'category' => 'Services'],
            ['name' => 'Update Services', 'slug' => 'services:update', 'category' => 'Services'],
            ['name' => 'Delete Services', 'slug' => 'services:delete', 'category' => 'Services'],
            ['name' => 'Read Enquiries', 'slug' => 'enquiries:read', 'category' => 'Enquiries'],
            ['name' => 'Manage Enquiries', 'slug' => 'enquiries:manage', 'category' => 'Enquiries'],
            ['name' => 'Read Bookings', 'slug' => 'bookings:read', 'category' => 'Bookings'],
            ['name' => 'Manage Bookings', 'slug' => 'bookings:manage', 'category' => 'Bookings'],
            ['name' => 'Manage Orders', 'slug' => 'orders:manage', 'category' => 'Orders'],
            ['name' => 'Manage Pages', 'slug' => 'pages:manage', 'category' => 'CMS'],
            ['name' => 'Manage Blogs', 'slug' => 'blogs:manage', 'category' => 'CMS'],
            ['name' => 'Manage FAQs', 'slug' => 'faqs:manage', 'category' => 'CMS'],
            ['name' => 'Manage Testimonials', 'slug' => 'testimonials:manage', 'category' => 'CMS'],
            ['name' => 'Manage Media', 'slug' => 'media:manage', 'category' => 'Media'],
            ['name' => 'Manage Users', 'slug' => 'users:manage', 'category' => 'System'],
            ['name' => 'Manage Roles', 'slug' => 'roles:manage', 'category' => 'System'],
            ['name' => 'Manage Settings', 'slug' => 'settings:manage', 'category' => 'Settings'],
            ['name' => 'Read Logs', 'slug' => 'logs:read', 'category' => 'System'],
        ];

        $permissionsMap = [];
        foreach ($permissionsList as $p) {
            $perm = Permission::firstOrCreate(
                ['slug' => $p['slug']],
                ['name' => $p['name'], 'category' => $p['category']]
            );
            $permissionsMap[$p['slug']] = $perm->id;
        }

        // 2. Roles
        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'super-admin'],
            [
                'name' => 'Super Admin',
                'description' => 'Full unrestricted access to all modules and configurations',
                'is_system' => true,
            ]
        );

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'name' => 'Admin',
                'description' => 'Standard administrator with full operational access',
                'is_system' => true,
            ]
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'name' => 'Manager',
                'description' => 'Operations manager for bookings, enquiries and CMS',
                'is_system' => true,
            ]
        );

        $staffRole = Role::firstOrCreate(
            ['slug' => 'staff'],
            [
                'name' => 'Staff',
                'description' => 'Field technician / cleaning specialist',
                'is_system' => true,
            ]
        );

        $customerRole = Role::firstOrCreate(
            ['slug' => 'customer'],
            [
                'name' => 'Customer',
                'description' => 'Registered website customer',
                'is_system' => true,
            ]
        );

        // Assign permissions to Super Admin
        foreach ($permissionsMap as $slug => $permId) {
            RolePermission::firstOrCreate([
                'role_id' => $superAdminRole->id,
                'permission_id' => $permId,
            ]);
            RolePermission::firstOrCreate([
                'role_id' => $adminRole->id,
                'permission_id' => $permId,
            ]);
        }

        // 3. Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@brisbane.com'],
            [
                'name' => 'Master Admin',
                'password' => Hash::make('Admin@123456'),
                'phone' => '0434 061 188',
                'status' => 'ACTIVE',
                'role_id' => $superAdminRole->id,
            ]
        );

        // 4. Service Categories
        $domesticCat = ServiceCategory::firstOrCreate(
            ['slug' => 'domestic-services'],
            [
                'name' => 'Domestic Services',
                'description' => 'Comprehensive residential cleaning and sanitation services',
                'icon' => '🏠',
                'order' => 1,
            ]
        );

        $commercialCat = ServiceCategory::firstOrCreate(
            ['slug' => 'commercial-services'],
            [
                'name' => 'Commercial Services',
                'description' => 'Professional commercial, industrial and specialized facility cleaning',
                'icon' => '🏢',
                'order' => 2,
            ]
        );

        // 5. Services
        $servicesList = [
            [
                'name' => 'Bond Cleaning Brisbane',
                'slug' => 'bond-cleaning-brisbane',
                'category_id' => $domesticCat->id,
                'short_desc' => 'Ensure you get your bond deposit back with our expert bond cleaning services in Brisbane.',
                'description' => 'Ensure you get your bond deposit back with our expert bond cleaning services in Brisbane. Our comprehensive cleaning packages are tailored to meet the strict standards of landlords and property managers. With flexible scheduling and a satisfaction guarantee, we make the moving process stress-free and efficient.',
                'price_starting' => 400,
                'price_unit' => 'Fixed',
                'duration' => '4-6 hours',
                'icon' => '🏠',
                'hero_image' => '/assets/home/image/brisbanecarpetpestexperts-img3-1.jpg',
                'features' => json_encode([
                    'Deep cleaning all rooms',
                    'Carpet and floor cleaning',
                    'Kitchen and appliance detailing',
                    'Window and blind cleaning',
                    'Bond back guarantee',
                ]),
                'tab_data' => json_encode([
                    'residential' => [
                        'title' => 'Residential',
                        'price' => 'From $400',
                        'features' => ['Deep cleaning all rooms', 'Carpet and floor cleaning', 'Kitchen detailing', 'Bond back guarantee'],
                    ],
                    'commercial' => [
                        'title' => 'Commercial',
                        'price' => 'From $600',
                        'features' => ['Office space cleaning', 'Carpet and floor care', 'Glass cleaning', 'Same-day availability'],
                    ],
                    'outdoor' => [
                        'title' => 'Outdoor',
                        'price' => 'From $300',
                        'features' => ['Pressure washing driveways', 'Patio & deck cleaning', 'Debris removal', 'Fence cleaning'],
                    ],
                ]),
                'meta_title' => 'Bond Cleaning Brisbane | 100% Bond Back Guarantee',
                'meta_desc' => 'Professional bond cleaning and end of lease cleaning in Brisbane. Guaranteed landlord approval and flexible booking.',
                'is_featured' => true,
                'order' => 1,
            ],
            [
                'name' => 'End Of Lease Cleaning Brisbane',
                'slug' => 'end-of-lease-cleaning-brisbane',
                'category_id' => $domesticCat->id,
                'short_desc' => 'Complete end-of-lease sanitization designed for quick inspection pass.',
                'description' => 'High-standard cleaning checklist covering every corner of your rental property before handover.',
                'price_starting' => 390,
                'price_unit' => 'Fixed',
                'duration' => '4-5 hours',
                'icon' => '🔑',
                'hero_image' => '/assets/home/image/brisbanecarpetpestexperts-img3-1.jpg',
                'features' => json_encode(['Oven & rangehood degreasing', 'Wall mark removals', 'Bathroom tile scrubbing', 'Carpet steam extraction']),
                'meta_title' => 'End Of Lease Cleaning Brisbane | Fast & Reliable',
                'meta_desc' => 'Stress-free end of lease cleaning services across Brisbane.',
                'is_featured' => true,
                'order' => 2,
            ],
            [
                'name' => 'Pest Control Brisbane',
                'slug' => 'pest-control-brisbane',
                'category_id' => $domesticCat->id,
                'short_desc' => 'Safe, pet-friendly and certified eradication of pests.',
                'description' => 'Comprehensive pest control for cockroaches, ants, spiders, rodents and termites tailored to Brisbane climate.',
                'price_starting' => 180,
                'price_unit' => 'Fixed',
                'duration' => '1-2 hours',
                'icon' => '🛡️',
                'hero_image' => '/assets/home/image/brisbanecarpetpestexperts-img2-1.jpg',
                'features' => json_encode(['Eco-safe treatments', 'Targeted pest elimination', 'Warranty on treatments', 'Child & pet safe']),
                'meta_title' => 'Pest Control Brisbane | Certified & Safe Pest Exterminators',
                'meta_desc' => 'Reliable and pet-safe pest control in Brisbane for homes and commercial facilities.',
                'is_featured' => true,
                'order' => 3,
            ],
            [
                'name' => 'Carpet Cleaning',
                'slug' => 'carpet-cleaning',
                'category_id' => $commercialCat->id,
                'short_desc' => 'Deep hot water extraction and steam carpet cleaning.',
                'description' => 'Eliminate stubborn stains, deep dust, allergens and pet odors with state-of-the-art steam extraction.',
                'price_starting' => 99,
                'price_unit' => 'Fixed',
                'duration' => '1-3 hours',
                'icon' => '🧼',
                'hero_image' => '/assets/about/carpet-service-1.jpg',
                'features' => json_encode(['Hot water extraction', 'Stain pre-treatment', 'Quick dry technology', 'Sanitization']),
                'meta_title' => 'Carpet Cleaning Brisbane | Steam & Stain Removal Experts',
                'meta_desc' => 'Revitalize your carpets with Brisbane trusted carpet steam cleaning service.',
                'is_featured' => true,
                'order' => 4,
            ],
            [
                'name' => 'Office Cleaning',
                'slug' => 'office-cleaning',
                'category_id' => $commercialCat->id,
                'short_desc' => 'Regular and deep janitorial cleaning for commercial workspaces.',
                'description' => 'Maintain a pristine, hygienic and productive office atmosphere for staff and visitors.',
                'price_starting' => 150,
                'price_unit' => 'Hourly',
                'duration' => '2-4 hours',
                'icon' => '🏢',
                'hero_image' => '/assets/about/office-clean.jpg',
                'features' => json_encode(['Workstation disinfection', 'Rubbish disposal', 'Restroom sanitization', 'Floor buffing']),
                'meta_title' => 'Commercial Office Cleaning Brisbane',
                'meta_desc' => 'Professional commercial office cleaning contracts and one-off cleans.',
                'is_featured' => false,
                'order' => 5,
            ],
            [
                'name' => 'Tile And Grout Cleaning',
                'slug' => 'tile-and-grout-cleaning',
                'category_id' => $commercialCat->id,
                'short_desc' => 'High-pressure restoration of tiled surfaces and grout lines.',
                'description' => 'Restore discolored tile and grout lines in kitchens, bathrooms and commercial lobbies.',
                'price_starting' => 160,
                'price_unit' => 'Fixed',
                'duration' => '2-3 hours',
                'icon' => '🧽',
                'hero_image' => '/assets/about/windowcleaning.jpg',
                'features' => json_encode(['High-pressure steam', 'Deep grout scrubbing', 'Protective sealing', 'Mold removal']),
                'meta_title' => 'Tile and Grout Cleaning Brisbane | Spotless Restoration',
                'meta_desc' => 'Bring back the original shine of your tiled floors and walls.',
                'is_featured' => false,
                'order' => 6,
            ],
        ];

        foreach ($servicesList as $s) {
            Service::updateOrCreate(['slug' => $s['slug']], $s);
        }

        // 6. Blog Categories & Blogs
        $blogCat = BlogCategory::firstOrCreate(
            ['slug' => 'cleaning-tips'],
            ['name' => 'Cleaning Tips']
        );

        Blog::firstOrCreate(
            ['slug' => 'choosing-right-carpet-pest-cleaning-brisbane'],
            [
                'title' => 'Choosing the Right Carpet and Pest Cleaning Service in Brisbane',
                'excerpt' => 'Selecting reliable carpet and pest cleaning services in Brisbane is crucial for maintaining a clean, healthy environment.',
                'content' => 'Selecting reliable carpet and pest cleaning services in Brisbane is crucial for maintaining a clean, healthy environment in homes and businesses.',
                'featured_img' => '/assets/blog/pages/carpet-and-pest-cleaning.jpg',
                'author' => 'Brisbane Carpet Experts',
                'read_time' => 5,
                'category_id' => $blogCat->id,
                'tags' => 'Carpet Cleaning, Pest Control, Brisbane Tips',
                'meta_title' => 'Choosing Carpet and Pest Cleaning in Brisbane',
                'meta_desc' => 'Key tips to select the best certified carpet and pest cleaners in Brisbane.',
                'status' => 'PUBLISHED',
            ]
        );

        // 7. FAQs
        $faqCat = FaqCategory::firstOrCreate(
            ['slug' => 'general-faqs'],
            ['name' => 'General Questions', 'order' => 1]
        );

        $faqs = [
            [
                'question' => "What services don't you offer?",
                'answer' => "We don't offer hazardous waste cleaning, heavy industrial machinery lifting, or outdoor construction demolition.",
                'category_id' => $faqCat->id,
                'order' => 1,
            ],
            [
                'question' => 'How far in advance should I book for bond cleaning?',
                'answer' => 'You can book as early as you like, or even request same-day service depending on team availability. We recommend 48-72 hours in advance.',
                'category_id' => $faqCat->id,
                'order' => 2,
            ],
            [
                'question' => 'Are your cleaning chemicals safe for children and pets?',
                'answer' => 'Yes! We prioritize eco-friendly, biodegradable, and non-toxic cleaning agents and pest solutions.',
                'category_id' => $faqCat->id,
                'order' => 3,
            ],
            [
                'question' => 'What if the real estate property manager is not satisfied?',
                'answer' => 'We provide a 100% Bond Back Guarantee. If any item on the inspection report requires touch up within 72 hours, we return and fix it at zero extra charge.',
                'category_id' => $faqCat->id,
                'order' => 4,
            ],
        ];

        foreach ($faqs as $f) {
            Faq::firstOrCreate(['question' => $f['question']], $f);
        }

        // 8. Testimonials
        $testimonials = [
            [
                'client_name' => 'Rebecca Hawland',
                'role' => 'Tenant',
                'location' => 'South Brisbane',
                'rating' => 5,
                'review' => 'Brisbane Carpet & Pest Experts did an incredible job on my 3-bedroom townhouse. The real estate agent passed the inspection immediately and I got 100% of my bond back!',
                'source' => 'Google',
                'is_approved' => true,
                'order' => 1,
            ],
            [
                'client_name' => 'David Nguyen',
                'role' => 'Homeowner',
                'location' => 'Chermside',
                'rating' => 5,
                'review' => 'Outstanding carpet steam cleaning. Removed deep stains that had been there for years. The team was prompt, courteous and professional.',
                'source' => 'Google',
                'is_approved' => true,
                'order' => 2,
            ],
        ];

        foreach ($testimonials as $t) {
            Testimonial::firstOrCreate(['client_name' => $t['client_name']], $t);
        }

        // 9. Site Settings
        $settings = [
            ['key' => 'site_name', 'value' => 'Brisbane Carpet & Pest Experts', 'group' => 'general', 'label' => 'Site Name'],
            ['key' => 'company_phone', 'value' => '0434 061 188', 'group' => 'contact', 'label' => 'Phone Number'],
            ['key' => 'company_email', 'value' => 'info@brisbanecarpetpestexperts.com.au', 'group' => 'contact', 'label' => 'Email Address'],
            ['key' => 'company_abn', 'value' => '45 892 103 441', 'group' => 'general', 'label' => 'Australian Business Number (ABN)'],
            ['key' => 'company_address', 'value' => '123 Queen Street, Brisbane CBD, QLD 4000', 'group' => 'contact', 'label' => 'Address'],
            ['key' => 'deposit_amount_default', 'value' => '50.00', 'group' => 'payment', 'label' => 'Default Deposit Amount (AUD)'],
            ['key' => 'stripe_enabled', 'value' => 'true', 'group' => 'payment', 'label' => 'Enable Stripe'],
            ['key' => 'paypal_enabled', 'value' => 'true', 'group' => 'payment', 'label' => 'Enable PayPal'],
            ['key' => 'payid_enabled', 'value' => 'true', 'group' => 'payment', 'label' => 'Enable PayID'],
            ['key' => 'bank_transfer_enabled', 'value' => 'true', 'group' => 'payment', 'label' => 'Enable Bank Transfer'],
            ['key' => 'whatsapp_enabled', 'value' => 'true', 'group' => 'whatsapp', 'label' => 'Enable WhatsApp'],
            ['key' => 'whatsapp_business_phone', 'value' => '0434 061 188', 'group' => 'whatsapp', 'label' => 'WhatsApp Phone'],
        ];

        foreach ($settings as $st) {
            SiteSetting::updateOrCreate(['key' => $st['key']], $st);
        }

        // 10. Default Employees / Technicians
        $employeeUser1 = User::firstOrCreate(
            ['email' => 'tech.marcus@brisbane.com'],
            [
                'name' => 'Marcus Vance',
                'password' => Hash::make('Staff@123456'),
                'phone' => '0412 884 901',
                'status' => 'ACTIVE',
                'role_id' => $staffRole->id,
            ]
        );

        $emp1 = Employee::firstOrCreate(
            ['email' => 'tech.marcus@brisbane.com'],
            [
                'employee_code' => 'EMP-101',
                'user_id' => $employeeUser1->id,
                'name' => 'Marcus Vance',
                'phone' => '0412 884 901',
                'designation' => 'Senior Steam Carpet & Bond Specialist',
                'department' => 'Cleaning & Pest Control',
                'hourly_rate' => 42.50,
                'skills' => json_encode(['Steam Cleaning', 'Bond Inspection', 'Stain Treatment']),
                'license_number' => 'QLD-PST-99182',
                'address' => '44 River Terrace, Kangaroo Point QLD 4169',
            ]
        );

        $employeeUser2 = User::firstOrCreate(
            ['email' => 'tech.liam@brisbane.com'],
            [
                'name' => 'Liam Connor',
                'password' => Hash::make('Staff@123456'),
                'phone' => '0419 772 310',
                'status' => 'ACTIVE',
                'role_id' => $staffRole->id,
            ]
        );

        $emp2 = Employee::firstOrCreate(
            ['email' => 'tech.liam@brisbane.com'],
            [
                'employee_code' => 'EMP-102',
                'user_id' => $employeeUser2->id,
                'name' => 'Liam Connor',
                'phone' => '0419 772 310',
                'designation' => 'Licensed Termite & Pest Exterminator',
                'department' => 'Pest Management',
                'hourly_rate' => 45.00,
                'skills' => json_encode(['Pest Eradication', 'Termite Barrier', 'Eco Sprays']),
                'license_number' => 'QLD-EX-8821',
                'address' => '12 Boundary St, West End QLD 4101',
            ]
        );

        // 11. Sample Customer & Bookings
        $customerUser = User::firstOrCreate(
            ['email' => 'sarah.miller@example.com'],
            [
                'name' => 'Sarah Miller',
                'password' => Hash::make('Customer@123456'),
                'phone' => '0491 570 006',
                'status' => 'ACTIVE',
                'role_id' => $customerRole->id,
            ]
        );

        $customer = Customer::firstOrCreate(
            ['email' => 'sarah.miller@example.com'],
            [
                'user_id' => $customerUser->id,
                'name' => 'Sarah Miller',
                'phone' => '0491 570 006',
                'address' => '78 Brunswick Street',
                'suburb' => 'Fortitude Valley',
                'postcode' => '4006',
            ]
        );

        $booking = Booking::firstOrCreate(
            ['booking_number' => 'BK-2026-001'],
            [
                'customer_id' => $customer->id,
                'customer_name' => 'Sarah Miller',
                'customer_email' => 'sarah.miller@example.com',
                'customer_phone' => '0491 570 006',
                'service_name' => 'Bond Cleaning Brisbane',
                'service_address' => '78 Brunswick Street, Fortitude Valley QLD 4006',
                'suburb' => 'Fortitude Valley',
                'postcode' => '4006',
                'scheduled_date' => now()->addDays(2),
                'time_slot' => 'Morning (8AM - 11AM)',
                'rooms' => 3,
                'bathrooms' => 2,
                'total_price' => 420.00,
                'approved_price' => 420.00,
                'deposit_required' => 50.00,
                'deposit_paid' => 50.00,
                'balance_due' => 370.00,
                'quote_status' => 'APPROVED',
                'status' => 'CONFIRMED',
                'payment_status' => 'DEPOSIT_PAID',
                'assigned_employee_id' => $emp1->id,
            ]
        );

        // Sample Enquiry
        Enquiry::firstOrCreate(
            ['email' => 'john.smith@gmail.com'],
            [
                'enquiry_number' => 'ENQ-2026-001',
                'first_name' => 'John',
                'last_name' => 'Smith',
                'phone' => '0422 111 333',
                'service' => 'Carpet Cleaning',
                'bedrooms' => '4',
                'bathrooms' => '2',
                'message' => 'Looking for carpet steam cleaning this weekend for a 4-bedroom house.',
                'status' => 'NEW',
            ]
        );
    }
}
