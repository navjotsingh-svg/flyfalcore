<?php

namespace App\Http\Controllers;

use App\Exceptions\DuffelException;
use App\Exceptions\PayPalException;
use App\Models\Booking;
use App\Services\Bookings\BookingCheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PayPalController extends Controller
{
    public function __construct(protected BookingCheckoutService $checkout) {}

    public function success(Request $request, string $reference): RedirectResponse
    {
        $booking = Booking::query()
            ->where('booking_reference', $reference)
            ->firstOrFail();

        $orderId = $request->string('token')->toString() ?: $booking->paypal_order_id;

        try {
            $this->checkout->completePayPal($booking, $orderId ?: null);
        } catch (PayPalException $exception) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->with('error', 'PayPal could not complete this payment: '.$exception->getMessage());
        } catch (DuffelException $exception) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->with('error', 'Payment received, but the airline ticket could not be issued automatically: '.\App\Support\AirlineCopy::publicMessage($exception->getMessage()));
        }

        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('success', 'Payment received. Your flight is confirmed.');
    }

    public function cancel(string $reference): RedirectResponse
    {
        $booking = Booking::query()
            ->where('booking_reference', $reference)
            ->firstOrFail();

        if ($booking->payment_status !== 'paid') {
            $booking->update([
                'payment_status' => 'unpaid',
                'status' => 'pending',
            ]);
        }

        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('error', 'PayPal checkout was cancelled. You can pay from this page when you are ready.');
    }

    public function retry(string $reference): RedirectResponse
    {
        $booking = Booking::query()
            ->where('booking_reference', $reference)
            ->firstOrFail();

        if ($booking->payment_status === 'paid') {
            return redirect()->route('bookings.show', $booking->booking_reference);
        }

        try {
            return redirect()->away($this->checkout->startPayPal($booking));
        } catch (PayPalException $exception) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->with('error', $exception->getMessage());
        }
    }

    public function issueTicket(string $reference): RedirectResponse
    {
        $booking = Booking::query()
            ->where('booking_reference', $reference)
            ->firstOrFail();

        if ($booking->source !== 'duffel' || $booking->payment_status !== 'paid') {
            return redirect()->route('bookings.show', $booking->booking_reference);
        }

        if (filled($booking->duffel_order_id)) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->with('success', 'This ticket is already issued.');
        }

        try {
            $this->checkout->createDuffelOrder($booking);
        } catch (DuffelException $exception) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->with('error', 'The airline ticket could not be issued: '.\App\Support\AirlineCopy::publicMessage($exception->getMessage()));
        }

        return redirect()
            ->route('bookings.show', $booking->booking_reference)
            ->with('success', 'Payment received. Your flight is confirmed.');
    }
}
