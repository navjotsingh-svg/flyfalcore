@extends('layouts.admin')

@section('title', 'Dashboard')
@section('heading', 'Dashboard')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($stats as $stat)
        <a href="{{ $stat['href'] }}" class="rounded-2xl bg-white border border-slate-200 p-5 hover:border-blue-300 transition">
            <p class="text-sm text-slate-500">{{ $stat['label'] }}</p>
            <p class="mt-2 text-3xl font-extrabold">{{ number_format($stat['value']) }}</p>
        </a>
    @endforeach
</div>

<div class="mt-6 grid md:grid-cols-2 gap-4">
    <div class="rounded-2xl bg-white border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Paid today</p>
        <p class="text-2xl font-extrabold">{{ $paidToday }}</p>
    </div>
    <div class="rounded-2xl bg-white border border-slate-200 p-5">
        <p class="text-sm text-slate-500">Awaiting payment</p>
        <p class="text-2xl font-extrabold">{{ $pendingPayments }}</p>
    </div>
</div>

<div class="mt-8 rounded-2xl bg-white border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h2 class="font-bold">Recent bookings</h2>
        <a href="{{ route('admin.bookings.index') }}" class="text-sm font-semibold text-blue-600">View all</a>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-500">
                <tr>
                    <th class="px-5 py-3">Reference</th>
                    <th class="px-5 py-3">Contact</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3">Payment</th>
                    <th class="px-5 py-3">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentBookings as $booking)
                    <tr class="border-t border-slate-100">
                        <td class="px-5 py-3"><a class="font-semibold text-blue-600" href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->booking_reference }}</a></td>
                        <td class="px-5 py-3">{{ $booking->contact_name }}<div class="text-slate-400">{{ $booking->contact_email }}</div></td>
                        <td class="px-5 py-3 capitalize">{{ str_replace('_', ' ', $booking->status) }}</td>
                        <td class="px-5 py-3 capitalize">{{ $booking->payment_status }}</td>
                        <td class="px-5 py-3">{{ $booking->formattedTotal() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No bookings yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
