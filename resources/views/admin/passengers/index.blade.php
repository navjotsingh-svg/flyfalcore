@extends('layouts.admin')
@section('title', 'Passengers')
@section('heading', 'Passengers')
@section('content')
<div class="flex flex-col sm:flex-row gap-3 justify-between mb-4">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search passengers" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-64">
        <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
    </form>
    <a href="{{ route('admin.passengers.create') }}" class="inline-flex items-center rounded-full bg-blue-600 text-white font-semibold px-4 py-2 text-sm">Add passenger</a>
</div>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">Name</th><th class="px-5 py-3">Booking</th><th class="px-5 py-3">Passport</th><th class="px-5 py-3">Seat</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($passengers as $passenger)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3 font-semibold">{{ $passenger->full_name }}</td>
                <td class="px-5 py-3"><a class="text-blue-600" href="{{ route('admin.bookings.show', $passenger->booking) }}">{{ $passenger->booking->booking_reference }}</a></td>
                <td class="px-5 py-3">{{ $passenger->passport_number ?: '—' }}</td>
                <td class="px-5 py-3">{{ $passenger->seat_number ?: '—' }}</td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a class="text-blue-600 font-semibold" href="{{ route('admin.passengers.edit', $passenger) }}">Edit</a>
                    <form action="{{ route('admin.passengers.destroy', $passenger) }}" method="POST" class="inline" onsubmit="return confirm('Delete this passenger?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No passengers found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $passengers->links() }}</div>
@endsection
