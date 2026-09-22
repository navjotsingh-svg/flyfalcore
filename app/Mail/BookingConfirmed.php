<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BookingConfirmed extends Mailable
{
    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing([
            'passengers',
            'flight.airline',
            'flight.originAirport',
            'flight.destinationAirport',
        ]);
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Falcore booking confirmed — '.$this->booking->booking_reference,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.booking-confirmed',
        );
    }
}
