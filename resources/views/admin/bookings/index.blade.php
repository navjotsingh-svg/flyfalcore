@extends('layouts.admin')
@section('title', 'Bookings')
@section('heading', 'Bookings')
@section('content')
<div class="flex flex-col sm:flex-row gap-3 justify-between mb-4">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search reference, email, status" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-72">
        <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
    </form>
    <a href="{{ route('admin.bookings.create') }}" class="inline-flex items-center rounded-full bg-blue-600 text-white font-semibold px-4 py-2 text-sm">Add booking</a>
</div>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">Reference</th><th class="px-5 py-3">Contact</th><th class="px-5 py-3">Route</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Payment</th><th class="px-5 py-3">Total</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($bookings as $booking)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3"><a class="font-semibold text-blue-600" href="{{ route('admin.bookings.show', $booking) }}">{{ $booking->booking_reference }}</a><div class="text-slate-400">{{ $booking->source }}</div></td>
                <td class="px-5 py-3">{{ $booking->contact_name }}<div class="text-slate-400">{{ $booking->contact_email }}</div></td>
                <td class="px-5 py-3">{{ $booking->routeLabel() }}</td>
                <td class="px-5 py-3 capitalize">{{ str_replace('_',' ', $booking->status) }}</td>
                <td class="px-5 py-3 capitalize">{{ $booking->payment_status }}</td>
                <td class="px-5 py-3">{{ $booking->formattedTotal() }}</td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a class="text-blue-600 font-semibold" href="{{ route('admin.bookings.edit', $booking) }}">Edit</a>
                    <form action="{{ route('admin.bookings.destroy', $booking) }}" method="POST" class="inline" onsubmit="return confirm('Delete this booking?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">No bookings found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $bookings->links() }}</div>
@endsection
