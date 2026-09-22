@extends('layouts.app')

@section('title', 'Contact')

@section('content')
@include('partials.page-hero', [
    'eyebrow' => 'Contact',
    'title' => "Let’s Build the<br>Next Journey.",
    'subtitle' => "Whether you’re a traveller, business, travel professional, or potential partner, we’d love to hear from you.",
])

<section class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-16 grid lg:grid-cols-2 gap-12">
    <div>
        <div class="space-y-5">
            <a href="tel:{{ $falcore['phones']['usa']['href'] }}" class="flex items-start gap-4 rounded-2xl border border-slate-100 p-5 hover:border-gold-400">
                <span class="h-11 w-11 rounded-full bg-navy-900 text-gold-400 inline-flex items-center justify-center"><i class="fa-solid fa-phone"></i></span>
                <span>
                    <span class="block text-xs uppercase tracking-wider text-slate-400">{{ $falcore['phones']['usa']['label'] }}</span>
                    <span class="block mt-1 text-lg font-semibold text-navy-900">{{ $falcore['phones']['usa']['display'] }}</span>
                </span>
            </a>
            <a href="tel:{{ $falcore['phones']['india']['href'] }}" class="flex items-start gap-4 rounded-2xl border border-slate-100 p-5 hover:border-gold-400">
                <span class="h-11 w-11 rounded-full bg-navy-900 text-gold-400 inline-flex items-center justify-center"><i class="fa-solid fa-phone"></i></span>
                <span>
                    <span class="block text-xs uppercase tracking-wider text-slate-400">{{ $falcore['phones']['india']['label'] }}</span>
                    <span class="block mt-1 text-lg font-semibold text-navy-900">{{ $falcore['phones']['india']['display'] }}</span>
                </span>
            </a>
            <a href="mailto:{{ $falcore['email'] }}" class="flex items-start gap-4 rounded-2xl border border-slate-100 p-5 hover:border-gold-400">
                <span class="h-11 w-11 rounded-full bg-navy-900 text-gold-400 inline-flex items-center justify-center"><i class="fa-regular fa-envelope"></i></span>
                <span>
                    <span class="block text-xs uppercase tracking-wider text-slate-400">Email</span>
                    <span class="block mt-1 text-lg font-semibold text-navy-900">{{ $falcore['email'] }}</span>
                </span>
            </a>
            <p class="text-sm text-slate-500">Website <a class="font-semibold text-navy-900" href="https://flyfalcore.com">www.flyfalcore.com</a></p>
            <p class="text-sm text-slate-500">{{ $falcore['company'] }}</p>
        </div>
    </div>
    @if($pendingOtp ?? null)
        <div class="rounded-[28px] border border-slate-100 bg-slate-50 p-6 sm:p-8">
            <h2 class="font-display text-2xl font-bold text-navy-900">Verify your email</h2>
            <p class="mt-2 text-sm text-slate-500">We need to confirm this address before we save your message.</p>
            <div class="mt-5">
                @include('partials.otp-verify')
            </div>
        </div>
    @else
    <form action="{{ route('contact.store') }}" method="POST" class="rounded-[28px] border border-slate-100 bg-slate-50 p-6 sm:p-8 space-y-4">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-500">Name</label>
            <input type="text" name="name" required value="{{ old('name') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
            @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Email</label>
            <input type="email" name="email" required value="{{ old('email') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
            @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Phone</label>
            <input type="text" name="phone" value="{{ old('phone') }}" class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Message</label>
            <textarea name="message" rows="5" required class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm">{{ old('message') }}</textarea>
            @error('message') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="w-full rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold py-3">Send verification code</button>
    </form>
    @endif
</section>
@endsection
