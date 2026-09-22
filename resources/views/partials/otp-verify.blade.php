@php
    $pendingOtp = $pendingOtp ?? null;
    $verifyAction = $verifyAction ?? route('otp.verify');
    $resendAction = $resendAction ?? route('otp.resend');
    $cancelAction = $cancelAction ?? route('otp.cancel');
@endphp
@if($pendingOtp)
    <div class="rounded-2xl border border-gold-400/40 bg-brand-50 px-4 py-4 space-y-3">
        <p class="text-sm text-navy-900">Enter the 6-digit code sent to <strong>{{ $pendingOtp['email'] }}</strong>.</p>
        <form action="{{ $verifyAction }}" method="POST" class="flex flex-col sm:flex-row gap-2">
            @csrf
            <input type="hidden" name="purpose" value="{{ $pendingOtp['purpose'] }}">
            <input type="text" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required placeholder="000000" autocomplete="one-time-code"
                   class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-center text-lg tracking-[0.4em] font-semibold">
            <button class="rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-5 py-2.5">Verify</button>
        </form>
        @error('code') <p class="text-red-600 text-xs">{{ $message }}</p> @enderror
        <div class="flex flex-wrap gap-3 text-xs">
            <form action="{{ $resendAction }}" method="POST">@csrf<input type="hidden" name="purpose" value="{{ $pendingOtp['purpose'] }}"><button class="font-semibold text-navy-900 hover:text-gold-600">Resend code</button></form>
            <form action="{{ $cancelAction }}" method="POST">@csrf<input type="hidden" name="purpose" value="{{ $pendingOtp['purpose'] }}"><button class="text-slate-500 hover:text-navy-900">Use a different email</button></form>
        </div>
    </div>
@endif
