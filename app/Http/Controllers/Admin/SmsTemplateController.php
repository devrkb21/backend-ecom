<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $templates = SmsService::getOrderSmsTemplates();
        $smsEnabled = (bool) Setting::getValue('integration', 'sms_enabled', false);

        return view('admin.settings.sms-templates', compact('templates', 'smsEnabled'));
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'templates' => ['nullable', 'array'],
            'templates.*.enabled' => ['nullable', 'boolean'],
            'templates.*.template' => ['nullable', 'string', 'max:500'],
        ]);

        $templates = $request->input('templates', []);

        foreach ($templates as $statusKey => $data) {
            // Sanitize the status key
            $statusKey = preg_replace('/[^a-z0-9_]/', '', strtolower($statusKey));
            if ($statusKey === '') {
                continue;
            }

            $enabled = isset($data['enabled']) && $data['enabled'];
            $template = trim((string) ($data['template'] ?? ''));

            Setting::setValue('sms_templates', "sms_enabled_{$statusKey}", $enabled ? '1' : '0', [
                'type' => 'boolean',
                'label' => "SMS enabled for {$statusKey}",
                'description' => "Whether to send SMS when order status changes to {$statusKey}.",
                'is_public' => false,
            ]);

            Setting::setValue('sms_templates', "sms_template_{$statusKey}", $template, [
                'type' => 'text',
                'label' => "SMS template for {$statusKey}",
                'description' => "SMS message template for {$statusKey} status.",
                'is_public' => false,
            ]);
        }

        Setting::clearCache('sms_templates');

        return redirect()
            ->route('admin.settings.sms-templates')
            ->with('success', 'SMS templates updated successfully.');
    }
}
