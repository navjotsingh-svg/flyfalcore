@extends('layouts.app')

@section('title', 'Forgot password')

@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <h1 class="text-3xl font-extrabold text-center">Forgot password</h1>
    <p class="mt-2 text-center text-slate-500 text-sm">Enter your account email and we will send a reset link.</p>

    <form action="{{ route('password.email') }}" method="POST" class="mt-8 rounded-[28px] border border-slate-100 bg-white p-6 sm:p-8 space-y-4 shadow-sm">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-500">Email</label>
            <input type="email" name="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold py-3">Send reset link</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-semibold text-navy-900 hover:text-gold-600">Back to sign in</a>
    </p>
</section>
@endsection
