@extends('layouts.admin')
@section('title', 'Flights')
@section('heading', 'Flights')
@section('content')
<div class="flex flex-col sm:flex-row gap-3 justify-between mb-4">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search flights" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-64">
        <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
    </form>
    <a href="{{ route('admin.flights.create') }}" class="inline-flex items-center rounded-full bg-blue-600 text-white font-semibold px-4 py-2 text-sm">Add flight</a>
</div>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">Flight</th><th class="px-5 py-3">Route</th><th class="px-5 py-3">Depart</th><th class="px-5 py-3">Price</th><th class="px-5 py-3">Seats</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($flights as $flight)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3 font-semibold">{{ $flight->airline->code }}{{ $flight->flight_number }}<div class="text-slate-400 font-normal">{{ $flight->airline->name }}</div></td>
                <td class="px-5 py-3">{{ $flight->originAirport->code }} → {{ $flight->destinationAirport->code }}</td>
                <td class="px-5 py-3">{{ $flight->departure_at->format('M j, H:i') }}</td>
                <td class="px-5 py-3">₹{{ number_format($flight->price, 0) }}</td>
                <td class="px-5 py-3">{{ $flight->available_seats }}/{{ $flight->total_seats }}</td>
                <td class="px-5 py-3 capitalize">{{ $flight->status }}</td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a class="text-blue-600 font-semibold" href="{{ route('admin.flights.edit', $flight) }}">Edit</a>
                    <form action="{{ route('admin.flights.destroy', $flight) }}" method="POST" class="inline" onsubmit="return confirm('Delete this flight?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="px-5 py-8 text-center text-slate-400">No flights found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $flights->links() }}</div>
@endsection
