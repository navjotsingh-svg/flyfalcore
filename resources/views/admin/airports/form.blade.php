@extends('layouts.admin')
@section('title', $airport->exists ? 'Edit airport' : 'Add airport')
@section('heading', $airport->exists ? 'Edit airport' : 'Add airport')
@section('content')
<form action="{{ $airport->exists ? route('admin.airports.update', $airport) : route('admin.airports.store') }}" method="POST" class="max-w-xl rounded-2xl bg-white border border-slate-200 p-6 space-y-4">
    @csrf
    @if($airport->exists) @method('PUT') @endif
    <div>
        <label class="text-xs font-semibold text-slate-500">Airport name</label>
        <input name="name" value="{{ old('name', $airport->name) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-500">IATA code</label>
            <input name="code" value="{{ old('code', $airport->code) }}" maxlength="3" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm uppercase">
            @error('code') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">City</label>
            <input name="city" value="{{ old('city', $airport->city) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Country</label>
        <input name="country" value="{{ old('country', $airport->country) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="text-xs font-semibold text-slate-500">Latitude</label>
            <input name="latitude" value="{{ old('latitude', $airport->latitude) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Longitude</label>
            <input name="longitude" value="{{ old('longitude', $airport->longitude) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $airport->is_active ?? true))> Active</label>
    <div class="flex gap-3">
        <button class="rounded-full bg-blue-600 text-white font-semibold px-5 py-2.5 text-sm">Save</button>
        <a href="{{ route('admin.airports.index') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm">Cancel</a>
    </div>
</form>
@endsection
