@extends('layouts.app')

@section('title', 'Create account')

@push('head')
<style>
    .signup-stage {
        background-color: #071222;
        background-image:
            linear-gradient(105deg, rgba(7,18,34,0.94) 0%, rgba(7,18,34,0.82) 42%, rgba(7,18,34,0.38) 100%),
            url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2400&q=80');
        background-size: cover;
        background-position: center 38%;
    }
</style>
@endpush

@section('content')
<section class="signup-stage">
    <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16 lg:py-20">
        <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
            <div class="text-white order-2 lg:order-1">
                <p class="text-xs font-semibold tracking-[0.22em] uppercase text-gold-400">Join Falcore</p>
                <h1 class="mt-4 font-display text-4xl sm:text-5xl lg:text-[3.4rem] font-bold leading-[1.12] max-w-xl">
                    Create your account.<br>Keep every journey close.
                </h1>
                <p class="mt-5 max-w-lg text-white/75 text-lg leading-relaxed">
                    Save travellers, reopen trips in one tap, and book the next flight without starting from scratch.
                </p>

                <ul class="mt-10 space-y-4 max-w-md">
                    @foreach([
                        ['fa-user-group', 'Saved passengers', 'Reuse names, dates of birth, and passport details like a frequent flyer profile.'],
                        ['fa-ticket', 'Trip history', 'Airline PNR, seats, and extra bags stay with your Falcore reference.'],
                        ['fa-plane-up', 'Faster checkout', 'Pick Economy to First, add a seat or bag, and continue to pay in minutes.'],
                    ] as $item)
                        <li class="flex gap-4">
                            <span class="mt-0.5 h-11 w-11 shrink-0 rounded-2xl bg-white/10 text-gold-400 inline-flex items-center justify-center ring-1 ring-white/10">
                                <i class="fa-solid {{ $item[0] }}"></i>
                            </span>
                            <span>
                                <span class="block font-semibold">{{ $item[1] }}</span>
                                <span class="block mt-1 text-sm text-white/65 leading-relaxed">{{ $item[2] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="order-1 lg:order-2 lg:justify-self-end w-full max-w-lg mx-auto lg:mx-0">
                <div class="rounded-[28px] bg-white shadow-search border border-white/60 p-6 sm:p-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-semibold tracking-[0.18em] uppercase text-gold-600">New traveller</p>
                            <h2 class="mt-2 font-display text-3xl font-bold text-navy-900">Create your Falcore account</h2>
                            <p class="mt-2 text-sm text-slate-500">It takes about a minute. Guest bookings on this email are added when you join.</p>
                        </div>
                        @include('partials.logo', ['class' => 'h-10 w-auto hidden sm:block', 'wrapper' => 'hidden sm:inline-flex shrink-0'])
                    </div>

                    <div class="mt-7">
                        @include('partials.otp-verify')
                    </div>

                    @unless($pendingOtp ?? null)
                    <form action="{{ route('signup.store') }}" method="POST" class="mt-7 space-y-4" x-data="{ show: false, confirm: false }">
                        @csrf
                        <div>
                            <label for="signup-name" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Full name</label>
                            <div class="relative mt-1.5">
                                <i class="fa-regular fa-user pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input id="signup-name" type="text" name="name" required value="{{ old('name') }}" autocomplete="name" placeholder="As on your passport"
                                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-10 pr-3 py-3 text-sm font-medium text-navy-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:border-gold-500">
                            </div>
                            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="signup-email" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
                            <div class="relative mt-1.5">
                                <i class="fa-regular fa-envelope pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                <input id="signup-email" type="email" name="email" required value="{{ old('email') }}" autocomplete="email" placeholder="you@email.com"
                                       class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-10 pr-3 py-3 text-sm font-medium text-navy-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:border-gold-500">
                            </div>
                            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label for="signup-password" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Password</label>
                                <div class="relative mt-1.5">
                                    <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                    <input id="signup-password" :type="show ? 'text' : 'password'" name="password" required autocomplete="new-password" minlength="8" placeholder="8+ characters"
                                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-10 pr-11 py-3 text-sm font-medium text-navy-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:border-gold-500">
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-navy-900" @click="show = !show" :aria-label="show ? 'Hide password' : 'Show password'">
                                        <i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                                @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="signup-password-confirmation" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Confirm password</label>
                                <div class="relative mt-1.5">
                                    <i class="fa-solid fa-lock pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                                    <input id="signup-password-confirmation" :type="confirm ? 'text' : 'password'" name="password_confirmation" required autocomplete="new-password" minlength="8" placeholder="Repeat password"
                                           class="w-full rounded-2xl border border-slate-200 bg-slate-50 pl-10 pr-11 py-3 text-sm font-medium text-navy-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:border-gold-500">
                                    <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-navy-900" @click="confirm = !confirm" :aria-label="confirm ? 'Hide password' : 'Show password'">
                                        <i class="fa-regular" :class="confirm ? 'fa-eye-slash' : 'fa-eye'"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="mt-2 w-full rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold py-3.5 transition shadow-sm">
                            Send verification code
                        </button>
                    </form>
                    @endunless

                    <p class="mt-6 text-center text-sm text-slate-500">
                        Already have an account?
                        <a href="{{ route('login') }}" class="font-semibold text-navy-900 hover:text-gold-600">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
