<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SiteSetting;
use App\Services\ActivityLogger;

class AdminSettingController extends Controller
{
    public function index(Request $request)
    {
        $group = $request->query('group');

        $query = SiteSetting::query();
        if ($group) {
            $query->where('group', $group);
        }

        $settings = $query->get();
        $map = [];
        foreach ($settings as $s) {
            $map[$s->key] = $s->value;
        }

        return response()->json([
            'success' => true,
            'data' => $map,
            'items' => $settings,
        ]);
    }

    public function store(Request $request)
    {
        $body = $request->all();
        $group = $request->input('group', 'general');

        foreach ($body as $key => $value) {
            if ($key === 'group') continue;

            if (is_string($value) || is_numeric($value) || is_bool($value)) {
                SiteSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => (string) $value,
                        'group' => $group,
                        'label' => strtoupper(str_replace('_', ' ', $key)),
                    ]
                );
            }
        }

        ActivityLogger::log(
            action: 'SETTINGS_CHANGE',
            module: 'Settings',
            entityId: $group,
            details: ['keys' => array_keys($body)],
            request: $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully!',
        ]);
    }
}
