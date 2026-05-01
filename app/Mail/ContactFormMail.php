<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Message: ' . ($this->contactMessage->subject ?: 'No Subject'),
            replyTo: [$this->contactMessage->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-form',
        );
    }
}
