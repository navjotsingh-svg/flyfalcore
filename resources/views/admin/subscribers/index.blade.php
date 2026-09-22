@extends('layouts.admin')
@section('title', 'Subscribers')
@section('heading', 'Newsletter')
@section('content')
<form method="GET" class="mb-4">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search emails" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-64">
    <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
</form>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">Email</th><th class="px-5 py-3">Verified</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($subscribers as $subscriber)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3 font-semibold">{{ $subscriber->email }}</td>
                <td class="px-5 py-3">{{ $subscriber->verified_at?->format('M j, Y H:i') ?: '—' }}</td>
                <td class="px-5 py-3 text-right">
                    <form action="{{ route('admin.subscribers.destroy', $subscriber) }}" method="POST" onsubmit="return confirm('Remove this subscriber?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="px-5 py-8 text-center text-slate-400">No subscribers yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $subscribers->links() }}</div>
@endsection
