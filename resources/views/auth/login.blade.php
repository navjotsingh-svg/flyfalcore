@extends('layouts.app')

@section('title', 'Sign in')

@section('content')
<section class="max-w-md mx-auto px-4 py-16">
    <h1 class="text-3xl font-extrabold text-center">Sign in</h1>
    <p class="mt-2 text-center text-slate-500 text-sm">View booking history and manage your Falcore account.</p>

    <form action="{{ route('login.store') }}" method="POST" class="mt-8 rounded-[28px] border border-slate-100 bg-white p-6 sm:p-8 space-y-4 shadow-sm">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-500">Email</label>
            <input type="email" name="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Password</label>
            <input type="password" name="password" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="remember" value="1"> Remember me
        </label>
        <button type="submit" class="w-full rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold py-3">Sign in</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        New to Falcore?
        <a href="{{ route('signup') }}" class="font-semibold text-navy-900 hover:text-gold-600">Create an account</a>
    </p>
</section>
@endsection
