<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;

class AdminPaymentSettingController extends Controller
{
    private const DEFAULT_PAYMENT_CONFIG = [
        'stripe_enabled' => 'true',
        'stripe_mode' => 'test',
        'stripe_publishable_key' => 'pk_test_51NxSAMPLE_AU_KEY_00192837465',
        'stripe_secret_key' => 'sk_test_51NxSAMPLE_AU_SECRET_881928374',
        'stripe_webhook_secret' => 'whsec_sample_stripe_au_webhook_key',
        'stripe_currency' => 'AUD',
        'paypal_enabled' => 'true',
        'paypal_mode' => 'sandbox',
        'paypal_client_id' => 'AZ_sample_paypal_au_client_id_9921',
        'paypal_secret' => 'EL_sample_paypal_au_secret_key_8812',
        'payid_enabled' => 'true',
        'payid_type' => 'Email',
        'payid_identifier' => 'info@brisbanecarpetpestexperts.com.au',
        'payid_account_name' => 'Brisbane Carpet & Pest Experts Pty Ltd',
        'payid_bsb' => '084-004',
        'payid_account_number' => '12-345-6789',
        'payid_instructions' => 'Pay instantly from any Australian bank app using Osko / PayID with zero processing fees.',
        'poli_enabled' => 'true',
        'poli_merchant_code' => 'POLI_AU_BCPE_4000',
        'poli_auth_code' => 'AUTH_KEY_SAMPLE_POLI_8829',
        'afterpay_enabled' => 'true',
        'afterpay_merchant_id' => 'AFTERPAY_AU_MERCHANT_99182',
        'afterpay_secret_key' => 'sk_afterpay_sample_secret_key',
        'bank_transfer_enabled' => 'true',
        'bank_name' => 'National Australia Bank (NAB) / Commonwealth Bank',
        'bank_account_name' => 'Brisbane Carpet & Pest Experts',
        'bank_bsb' => '084-004',
        'bank_account_number' => '987654321',
        'bank_instructions' => 'Please include your Booking Reference (e.g. BK-XXXX) in the payment description.',
        'deposit_amount_default' => '50.00',
        'deposit_currency' => 'AUD',
        'deposit_refundable' => 'true',
        'deposit_auto_confirm' => 'true',
        'deposit_policy_note' => 'Standard refundable $50 AUD deposit to lock in certified specialist arrival date and time slot.',
    ];

    public function index(Request $request)
    {
        $settings = SiteSetting::where('group', 'payment')->get();
        $configMap = self::DEFAULT_PAYMENT_CONFIG;

        foreach ($settings as $s) {
            $configMap[$s->key] = $s->value;
        }

        return response()->json(['success' => true, 'data' => $configMap]);
    }

    public function store(Request $request)
    {
        $body = $request->all();

        foreach ($body as $key => $value) {
            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                SiteSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => (string) $value,
                        'group' => 'payment',
                        'label' => strtoupper(str_replace('_', ' ', $key)),
                    ]
                );
            }
        }

        ActivityLogger::log(
            action: 'SETTINGS_CHANGE',
            module: 'PaymentGateways',
            entityId: 'payment_settings',
            details: ['keys' => array_keys($body)],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Payment gateway settings and API keys updated successfully!',
        ]);
    }
}
