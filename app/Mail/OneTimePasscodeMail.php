<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OneTimePasscodeMail extends Mailable
{
    public function __construct(
        public string $code,
        public string $purpose,
        public string $email,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Falcore verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.otp',
        );
    }
}
