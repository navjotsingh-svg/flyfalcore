@extends('layouts.app')

@section('title', 'Reset password')

@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <h1 class="text-3xl font-extrabold text-center">Reset password</h1>
    <p class="mt-2 text-center text-slate-500 text-sm">Choose a new password for your Falcore account.</p>

    <form action="{{ route('password.update') }}" method="POST" class="mt-8 rounded-[28px] border border-slate-100 bg-white p-6 sm:p-8 space-y-4 shadow-sm">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="text-xs font-semibold text-slate-500">Email</label>
            <input type="email" name="email" required value="{{ old('email', $email) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">New password</label>
            <input type="password" name="password" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Confirm password</label>
            <input type="password" name="password_confirmation" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
        <button type="submit" class="w-full rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold py-3">Update password</button>
    </form>
</section>
@endsection
