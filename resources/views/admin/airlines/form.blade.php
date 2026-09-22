@extends('layouts.admin')
@section('title', $airline->exists ? 'Edit airline' : 'Add airline')
@section('heading', $airline->exists ? 'Edit airline' : 'Add airline')
@section('content')
<form action="{{ $airline->exists ? route('admin.airlines.update', $airline) : route('admin.airlines.store') }}" method="POST" class="max-w-xl rounded-2xl bg-white border border-slate-200 p-6 space-y-4">
    @csrf
    @if($airline->exists) @method('PUT') @endif
    <div>
        <label class="text-xs font-semibold text-slate-500">Name</label>
        <input name="name" value="{{ old('name', $airline->name) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">IATA code</label>
        <input name="code" value="{{ old('code', $airline->code) }}" maxlength="3" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm uppercase">
        @error('code') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Country</label>
        <input name="country" value="{{ old('country', $airline->country) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Logo URL</label>
        <input name="logo" value="{{ old('logo', $airline->logo) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $airline->is_active ?? true))> Active</label>
    <div class="flex gap-3">
        <button class="rounded-full bg-blue-600 text-white font-semibold px-5 py-2.5 text-sm">Save</button>
        <a href="{{ route('admin.airlines.index') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm">Cancel</a>
    </div>
</form>
@endsection
