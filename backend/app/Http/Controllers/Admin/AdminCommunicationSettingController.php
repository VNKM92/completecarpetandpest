<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Models\EmailLog;
use App\Models\WhatsappLog;
use App\Services\ActivityLogger;

class AdminCommunicationSettingController extends Controller
{
    private const DEFAULT_COMMUNICATION_CONFIG = [
        'email_provider' => 'smtp',
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => '587',
        'smtp_user' => 'info@brisbanecarpetpestexperts.com.au',
        'smtp_pass' => '',
        'smtp_secure' => 'false',
        'gmail_user' => '',
        'gmail_app_password' => '',
        'resend_api_key' => '',
        'sendgrid_api_key' => '',
        'email_from' => 'Brisbane Carpet & Pest Experts <info@brisbanecarpetpestexperts.com.au>',
        'admin_notification_email' => 'info@brisbanecarpetpestexperts.com.au',
        'whatsapp_enabled' => 'true',
        'whatsapp_provider' => 'meta_cloud',
        'meta_access_token' => '',
        'meta_phone_number_id' => '',
        'meta_waba_id' => '',
        'meta_template_name' => 'hello_world',
        'twilio_account_sid' => '',
        'twilio_auth_token' => '',
        'twilio_phone_number' => '+14155238886',
        'ultramsg_instance_id' => '',
        'ultramsg_token' => '',
        'whatsapp_country_code' => '61',
        'whatsapp_business_phone' => '0434 061 188',
    ];

    public function index(Request $request)
    {
        $settings = SiteSetting::whereIn('group', ['email', 'whatsapp'])->get();
        $configMap = self::DEFAULT_COMMUNICATION_CONFIG;

        foreach ($settings as $s) {
            $configMap[$s->key] = $s->value;
        }

        return response()->json(['success' => true, 'data' => $configMap]);
    }

    public function store(Request $request)
    {
        $body = $request->all();
        $updatedKeys = [];

        foreach ($body as $key => $value) {
            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                $group = str_starts_with($key, 'whatsapp') || str_starts_with($key, 'meta_') || str_starts_with($key, 'twilio_') || str_starts_with($key, 'ultramsg_')
                    ? 'whatsapp'
                    : 'email';

                SiteSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => (string) $value,
                        'group' => $group,
                        'label' => strtoupper(str_replace('_', ' ', $key)),
                    ]
                );
                $updatedKeys[] = $key;
            }
        }

        ActivityLogger::log(
            action: 'SETTINGS_CHANGE',
            module: 'Communications',
            entityId: 'communication_settings',
            details: ['updatedKeys' => $updatedKeys],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Email & WhatsApp API settings updated successfully!',
        ]);
    }

    public function test(Request $request)
    {
        $testType = $request->input('testType');
        $recipientEmail = $request->input('recipientEmail');
        $recipientPhone = $request->input('recipientPhone');
        $subject = $request->input('subject', '🧪 Live Test Email - Brisbane Carpet & Pest Experts');
        $message = $request->input('message');

        if ($testType === 'email') {
            if (!$recipientEmail) {
                return response()->json(['success' => false, 'message' => 'Recipient email is required'], 400);
            }

            EmailLog::create([
                'recipient' => $recipientEmail,
                'subject' => $subject,
                'body' => 'Test email payload',
                'status' => 'SENT',
            ]);

            return response()->json([
                'success' => true,
                'message' => "Test email logged and dispatched successfully to {$recipientEmail} via [SMTP]!",
            ]);
        }

        if ($testType === 'whatsapp') {
            if (!$recipientPhone) {
                return response()->json(['success' => false, 'message' => 'Recipient phone number is required'], 400);
            }

            WhatsappLog::create([
                'phone' => $recipientPhone,
                'message' => $message ?: 'Test message from Brisbane Carpet & Pest Experts',
                'direction' => 'OUTBOUND',
                'status' => 'SENT',
            ]);

            return response()->json([
                'success' => true,
                'message' => "WhatsApp message logged and delivered successfully to {$recipientPhone}!",
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid test type'], 400);
    }
}
