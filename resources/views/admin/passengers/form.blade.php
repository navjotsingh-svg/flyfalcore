@extends('layouts.admin')
@section('title', $passenger->exists ? 'Edit passenger' : 'Add passenger')
@section('heading', $passenger->exists ? 'Edit passenger' : 'Add passenger')
@section('content')
<form action="{{ $passenger->exists ? route('admin.passengers.update', $passenger) : route('admin.passengers.store') }}" method="POST" class="max-w-xl rounded-2xl bg-white border border-slate-200 p-6 space-y-4">
    @csrf
    @if($passenger->exists) @method('PUT') @endif
    <div>
        <label class="text-xs font-semibold text-slate-500">Booking</label>
        <select name="booking_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach($bookings as $booking)
                <option value="{{ $booking->id }}" @selected(old('booking_id', $passenger->booking_id) == $booking->id)>{{ $booking->booking_reference }} — {{ $booking->contact_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Title</label>
        <select name="title" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            <option value="">—</option>
            @foreach(['mr','mrs','ms','miss','dr'] as $title)
                <option value="{{ $title }}" @selected(old('title', $passenger->title) === $title)>{{ $title }}</option>
            @endforeach
        </select>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-500">First name</label>
            <input name="first_name" value="{{ old('first_name', $passenger->first_name) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Last name</label>
            <input name="last_name" value="{{ old('last_name', $passenger->last_name) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <input type="date" name="date_of_birth" value="{{ old('date_of_birth', optional($passenger->date_of_birth)->format('Y-m-d')) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Gender</label>
            <select name="gender" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                <option value="">—</option>
                @foreach(['male','female','other'] as $gender)
                    <option value="{{ $gender }}" @selected(old('gender', $passenger->gender) === $gender)>{{ $gender }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Passport</label>
        <input name="passport_number" value="{{ old('passport_number', $passenger->passport_number) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Nationality</label>
        <input name="nationality" value="{{ old('nationality', $passenger->nationality) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Seat</label>
        <input name="seat_number" value="{{ old('seat_number', $passenger->seat_number) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Airline passenger ID</label>
        <input name="duffel_passenger_id" value="{{ old('duffel_passenger_id', $passenger->duffel_passenger_id) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div class="flex gap-3">
        <button class="rounded-full bg-blue-600 text-white font-semibold px-5 py-2.5 text-sm">Save</button>
        <a href="{{ route('admin.passengers.index') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm">Cancel</a>
    </div>
</form>
@endsection
