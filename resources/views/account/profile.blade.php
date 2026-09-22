@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <p class="text-xs font-semibold tracking-[0.18em] uppercase text-gold-600">Account</p>
    <h1 class="mt-2 text-3xl font-extrabold">Profile</h1>
    <p class="mt-2 text-sm text-slate-500">
        <a href="{{ route('account.bookings') }}" class="font-semibold text-navy-900">← My trips</a>
        ·
        <a href="{{ route('account.passengers') }}" class="font-semibold text-navy-900">Saved passengers</a>
    </p>

    <form action="{{ route('account.profile.update') }}" method="POST" class="mt-8 rounded-[28px] border border-slate-100 bg-white p-6 sm:p-8 space-y-4 shadow-sm">
        @csrf
        @method('PUT')
        <div>
            <label class="text-xs font-semibold text-slate-500">Full name</label>
            <input type="text" name="name" required value="{{ old('name', $user->name) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Email</label>
            <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">New password</label>
            <input type="password" name="password" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm" placeholder="Leave blank to keep current">
            @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Confirm new password</label>
            <input type="password" name="password_confirmation" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
        <button type="submit" class="w-full rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold py-3">Save changes</button>
    </form>
</section>
@endsection
