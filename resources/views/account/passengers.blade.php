@extends('layouts.app')

@section('title', 'Saved passengers')

@section('content')
@php
    $isEditing = $editing->exists;
@endphp
<section class="max-w-[1100px] mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="text-xs font-semibold tracking-[0.18em] uppercase text-gold-600">Account</p>
            <h1 class="mt-2 font-display text-4xl font-bold">Saved passengers</h1>
            <p class="mt-2 text-slate-500">Reuse these travellers at checkout, like RedBus.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('account.bookings') }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">My trips</a>
            <a href="{{ route('account.profile') }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Profile</a>
        </div>
    </div>

    <div class="mt-8 grid lg:grid-cols-5 gap-8">
        <div class="lg:col-span-3 space-y-3">
            @forelse($passengers as $passenger)
                <article class="rounded-2xl border border-slate-100 bg-white p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="font-semibold text-lg">{{ $passenger->full_name }}</h2>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ optional($passenger->date_of_birth)?->format('d/m/Y') ?: 'DOB not saved' }}
                            @if($passenger->gender) · {{ ucfirst($passenger->gender) }} @endif
                            @if($passenger->passport_number) · {{ $passenger->passport_number }} @endif
                        </p>
                    </div>
                    <div class="flex items-center gap-3 text-sm font-semibold">
                        <a href="{{ route('account.passengers', ['edit' => $passenger->id]) }}" class="text-gold-600 hover:text-gold-500">Edit</a>
                        <form action="{{ route('account.passengers.destroy', $passenger) }}" method="POST" onsubmit="return confirm('Remove this saved passenger?')">
                            @csrf
                            @method('DELETE')
                            <button class="text-red-600">Remove</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-200 px-6 py-14 text-center">
                    <p class="font-semibold">No saved passengers yet</p>
                    <p class="mt-2 text-sm text-slate-500">Add a traveller here, or save them while booking a flight.</p>
                </div>
            @endforelse
        </div>

        <div class="lg:col-span-2">
            <form action="{{ $isEditing ? route('account.passengers.update', $editing) : route('account.passengers.store') }}" method="POST" class="rounded-[28px] border border-slate-100 bg-white p-6 space-y-4 shadow-sm">
                @csrf
                @if($isEditing) @method('PUT') @endif
                <h2 class="font-semibold text-lg">{{ $isEditing ? 'Edit passenger' : 'Add passenger' }}</h2>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Title</label>
                    <select name="title" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm bg-slate-50">
                        <option value="">Optional</option>
                        @foreach(['mr' => 'Mr', 'mrs' => 'Mrs', 'ms' => 'Ms', 'miss' => 'Miss', 'dr' => 'Dr'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('title', $editing->title) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-semibold text-slate-500">First name</label>
                        <input type="text" name="first_name" required value="{{ old('first_name', $editing->first_name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        @error('first_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="text-xs font-semibold text-slate-500">Last name</label>
                        <input type="text" name="last_name" required value="{{ old('last_name', $editing->last_name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                        @error('last_name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
                @include('partials.dob-selects', [
                    'dayName' => 'dob_day',
                    'monthName' => 'dob_month',
                    'yearName' => 'dob_year',
                    'hiddenName' => 'date_of_birth',
                    'value' => old('date_of_birth', optional($editing->date_of_birth)?->format('Y-m-d')),
                    'required' => false,
                ])
                @error('date_of_birth') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                <div>
                    <label class="text-xs font-semibold text-slate-500">Gender</label>
                    <select name="gender" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm bg-slate-50">
                        <option value="">Optional</option>
                        <option value="male" @selected(old('gender', $editing->gender) === 'male')>Male</option>
                        <option value="female" @selected(old('gender', $editing->gender) === 'female')>Female</option>
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Passport number</label>
                    <input type="text" name="passport_number" value="{{ old('passport_number', $editing->passport_number) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Nationality</label>
                    <input type="text" name="nationality" value="{{ old('nationality', $editing->nationality) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <div class="flex gap-3">
                    <button class="rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-5 py-2.5 text-sm">{{ $isEditing ? 'Update passenger' : 'Save passenger' }}</button>
                    @if($isEditing)
                        <a href="{{ route('account.passengers') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm font-semibold">Cancel</a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
