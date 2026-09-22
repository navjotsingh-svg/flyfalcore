@extends('layouts.admin')
@section('title', 'Airports')
@section('heading', 'Airports')
@section('content')
<div class="flex flex-col sm:flex-row gap-3 justify-between mb-4">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search airports" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-64">
        <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
    </form>
    <a href="{{ route('admin.airports.create') }}" class="inline-flex items-center rounded-full bg-blue-600 text-white font-semibold px-4 py-2 text-sm">Add airport</a>
</div>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">City</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Airport</th><th class="px-5 py-3">Country</th><th class="px-5 py-3">Active</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($airports as $airport)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3 font-semibold">{{ $airport->city }}</td>
                <td class="px-5 py-3">{{ $airport->code }}</td>
                <td class="px-5 py-3">{{ $airport->name }}</td>
                <td class="px-5 py-3">{{ $airport->country }}</td>
                <td class="px-5 py-3">{{ $airport->is_active ? 'Yes' : 'No' }}</td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a class="text-blue-600 font-semibold" href="{{ route('admin.airports.edit', $airport) }}">Edit</a>
                    <form action="{{ route('admin.airports.destroy', $airport) }}" method="POST" class="inline" onsubmit="return confirm('Delete this airport?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-5 py-8 text-center text-slate-400">No airports found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $airports->links() }}</div>
@endsection
