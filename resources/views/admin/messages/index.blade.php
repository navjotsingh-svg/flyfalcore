@extends('layouts.admin')
@section('title', 'Messages')
@section('heading', 'Contact messages')
@section('content')
<form method="GET" class="mb-4">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search messages" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-64">
    <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
</form>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">From</th><th class="px-5 py-3">Message</th><th class="px-5 py-3">Received</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($messages as $message)
            <tr class="border-t border-slate-100 align-top">
                <td class="px-5 py-3">
                    <p class="font-semibold">{{ $message->name }}</p>
                    <p class="text-slate-500">{{ $message->email }}</p>
                    <p class="text-slate-400">{{ $message->phone ?: '—' }}</p>
                </td>
                <td class="px-5 py-3 max-w-xl">{{ $message->message }}</td>
                <td class="px-5 py-3 text-slate-500">{{ $message->created_at?->format('M j, Y H:i') }}</td>
                <td class="px-5 py-3 text-right">
                    <form action="{{ route('admin.messages.destroy', $message) }}" method="POST" onsubmit="return confirm('Delete this message?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">No messages yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $messages->links() }}</div>
@endsection
