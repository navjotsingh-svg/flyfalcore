@extends('layouts.admin')
@section('title', $flight->exists ? 'Edit flight' : 'Add flight')
@section('heading', $flight->exists ? 'Edit flight' : 'Add flight')
@section('content')
<form action="{{ $flight->exists ? route('admin.flights.update', $flight) : route('admin.flights.store') }}" method="POST" class="max-w-3xl rounded-2xl bg-white border border-slate-200 p-6 grid sm:grid-cols-2 gap-4">
    @csrf
    @if($flight->exists) @method('PUT') @endif
    <div>
        <label class="text-xs font-semibold text-slate-500">Airline</label>
        <select name="airline_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach($airlines as $airline)
                <option value="{{ $airline->id }}" @selected(old('airline_id', $flight->airline_id) == $airline->id)>{{ $airline->name }} ({{ $airline->code }})</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Flight number</label>
        <input name="flight_number" value="{{ old('flight_number', $flight->flight_number) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        @error('flight_number') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Origin</label>
        <select name="origin_airport_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach($airports as $airport)
                <option value="{{ $airport->id }}" @selected(old('origin_airport_id', $flight->origin_airport_id) == $airport->id)>{{ $airport->display_name }}</option>
            @endforeach
        </select>
        @error('origin_airport_id') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Destination</label>
        <select name="destination_airport_id" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach($airports as $airport)
                <option value="{{ $airport->id }}" @selected(old('destination_airport_id', $flight->destination_airport_id) == $airport->id)>{{ $airport->display_name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Departure</label>
        <input type="datetime-local" name="departure_at" value="{{ old('departure_at', optional($flight->departure_at)->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Arrival</label>
        <input type="datetime-local" name="arrival_at" value="{{ old('arrival_at', optional($flight->arrival_at)->format('Y-m-d\TH:i')) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        @error('arrival_at') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Duration (minutes)</label>
        <input type="number" name="duration_minutes" value="{{ old('duration_minutes', $flight->duration_minutes) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="Auto if blank">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Price (INR)</label>
        <input type="number" step="0.01" name="price" value="{{ old('price', $flight->price) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Cabin</label>
        <select name="cabin_class" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach(['economy','premium_economy','business','first'] as $cabin)
                <option value="{{ $cabin }}" @selected(old('cabin_class', $flight->cabin_class) === $cabin)>{{ $cabin }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Status</label>
        <select name="status" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @foreach(['scheduled','delayed','cancelled','completed'] as $status)
                <option value="{{ $status }}" @selected(old('status', $flight->status) === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Total seats</label>
        <input type="number" name="total_seats" value="{{ old('total_seats', $flight->total_seats) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Available seats</label>
        <input type="number" name="available_seats" value="{{ old('available_seats', $flight->available_seats) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div class="sm:col-span-2 flex gap-3 pt-2">
        <button class="rounded-full bg-blue-600 text-white font-semibold px-5 py-2.5 text-sm">Save</button>
        <a href="{{ route('admin.flights.index') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm">Cancel</a>
    </div>
</form>
@endsection
