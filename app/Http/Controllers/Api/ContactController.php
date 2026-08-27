<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ContactFormMail;
use App\Models\ContactMessage;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        $contactMessage = ContactMessage::create($validated);

        // Send email to support
        try {
            $supportEmail = Setting::getValue('general', 'contact_email');

            if ($supportEmail) {
                Mail::to($supportEmail)->send(new ContactFormMail($contactMessage));
            }
        } catch (\Exception $e) {
            // Log the error but don't fail the request
            \Log::error('Failed to send contact email: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Thank you! Your message has been sent successfully.',
        ], 201);
    }
}
