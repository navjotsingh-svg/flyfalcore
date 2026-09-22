@extends('layouts.admin')
@section('title', $booking->exists ? 'Edit booking' : 'Add booking')
@section('heading', $booking->exists ? 'Edit booking' : 'Add booking')
@section('content')
<form action="{{ $booking->exists ? route('admin.bookings.update', $booking) : route('admin.bookings.store') }}" method="POST" class="max-w-3xl rounded-2xl bg-white border border-slate-200 p-6 grid sm:grid-cols-2 gap-4">
    @csrf
    @if($booking->exists) @method('PUT') @endif
    <div>
        <label class="text-xs font-semibold text-slate-500">Source</label>
        <select name="source" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            <option value="local" @selected(old('source', $booking->source) === 'local')>local</option>
            <option value="duffel" @selected(old('source', $booking->source) === 'duffel')>Live airline</option>
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">User</label>
        <select name="user_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            <option value="">None</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('user_id', $booking->user_id) == $user->id)>{{ $user->name }} — {{ $user->email }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-semibold text-slate-500">Local flight</label>
        <select name="flight_id" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            <option value="">None / live fare only</option>
            @foreach($flights as $flight)
                <option value="{{ $flight->id }}" @selected(old('flight_id', $booking->flight_id) == $flight->id)>
                    {{ $flight->airline->code }}{{ $flight->flight_number }} · {{ $flight->originAirport->code }}-{{ $flight->destinationAirport->code }} · {{ $flight->departure_at->format('M j H:i') }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Contact name</label>
        <input name="contact_name" value="{{ old('contact_name', $booking->contact_name) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Contact email</label>
        <input type="email" name="contact_email" value="{{ old('contact_email', $booking->contact_email) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Phone</label>
        <input name="contact_phone" value="{{ old('contact_phone', $booking->contact_phone) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Passengers count</label>
        <input type="number" name="passengers_count" value="{{ old('passengers_count', $booking->passengers_count) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Total amount</label>
        <input type="number" step="0.01" name="total_amount" value="{{ old('total_amount', $booking->total_amount) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Currency</label>
        <input name="currency" value="{{ old('currency', $booking->currency ?: 'INR') }}" maxlength="3" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm uppercase">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Status</label>
        <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach(['pending','confirmed','cancelled','completed','fulfillment_failed'] as $status)
                <option value="{{ $status }}" @selected(old('status', $booking->status) === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Payment status</label>
        <select name="payment_status" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach(['unpaid','pending','paid','refunded','failed'] as $status)
                <option value="{{ $status }}" @selected(old('payment_status', $booking->payment_status) === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Airline offer ID</label>
        <input name="duffel_offer_id" value="{{ old('duffel_offer_id', $booking->duffel_offer_id) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Airline order ID</label>
        <input name="duffel_order_id" value="{{ old('duffel_order_id', $booking->duffel_order_id) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Airline PNR</label>
        <input name="duffel_booking_reference" value="{{ old('duffel_booking_reference', $booking->duffel_booking_reference) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">PayPal order ID</label>
        <input name="paypal_order_id" value="{{ old('paypal_order_id', $booking->paypal_order_id) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">PayPal capture ID</label>
        <input name="paypal_capture_id" value="{{ old('paypal_capture_id', $booking->paypal_capture_id) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Booked at</label>
        <input type="datetime-local" name="booked_at" value="{{ old('booked_at', optional($booking->booked_at)->format('Y-m-d\TH:i')) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-semibold text-slate-500">Fulfillment error</label>
        <textarea name="fulfillment_error" rows="3" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">{{ old('fulfillment_error', $booking->fulfillment_error) }}</textarea>
    </div>
    <div class="sm:col-span-2 flex gap-3">
        <button class="rounded-full bg-blue-600 text-white font-semibold px-5 py-2.5 text-sm">Save</button>
        <a href="{{ route('admin.bookings.index') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm">Cancel</a>
    </div>
</form>
@endsection
