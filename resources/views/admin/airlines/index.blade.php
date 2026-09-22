@extends('layouts.admin')
@section('title', 'Airlines')
@section('heading', 'Airlines')
@section('content')
<div class="flex flex-col sm:flex-row gap-3 justify-between mb-4">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search airlines" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-64">
        <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
    </form>
    <a href="{{ route('admin.airlines.create') }}" class="inline-flex items-center rounded-full bg-blue-600 text-white font-semibold px-4 py-2 text-sm">Add airline</a>
</div>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">Name</th><th class="px-5 py-3">Code</th><th class="px-5 py-3">Country</th><th class="px-5 py-3">Active</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($airlines as $airline)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3 font-semibold">{{ $airline->name }}</td>
                <td class="px-5 py-3">{{ $airline->code }}</td>
                <td class="px-5 py-3">{{ $airline->country }}</td>
                <td class="px-5 py-3">{{ $airline->is_active ? 'Yes' : 'No' }}</td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a class="text-blue-600 font-semibold" href="{{ route('admin.airlines.edit', $airline) }}">Edit</a>
                    <form action="{{ route('admin.airlines.destroy', $airline) }}" method="POST" class="inline" onsubmit="return confirm('Delete this airline?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-5 py-8 text-center text-slate-400">No airlines found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $airlines->links() }}</div>
@endsection
