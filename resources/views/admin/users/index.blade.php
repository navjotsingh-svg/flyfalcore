@extends('layouts.admin')
@section('title', 'Users')
@section('heading', 'Users')
@section('content')
<div class="flex flex-col sm:flex-row gap-3 justify-between mb-4">
    <form method="GET" class="flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Search users" class="rounded-xl border border-slate-200 px-3 py-2 text-sm w-64">
        <button class="rounded-xl bg-white border border-slate-200 px-3 py-2 text-sm font-semibold">Search</button>
    </form>
    <a href="{{ route('admin.users.create') }}" class="inline-flex items-center rounded-full bg-blue-600 text-white font-semibold px-4 py-2 text-sm">Add user</a>
</div>
<div class="rounded-2xl bg-white border border-slate-200 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-500"><tr>
            <th class="px-5 py-3">Name</th><th class="px-5 py-3">Email</th><th class="px-5 py-3">Admin</th><th class="px-5 py-3"></th>
        </tr></thead>
        <tbody>
        @forelse($users as $user)
            <tr class="border-t border-slate-100">
                <td class="px-5 py-3 font-semibold">{{ $user->name }}</td>
                <td class="px-5 py-3">{{ $user->email }}</td>
                <td class="px-5 py-3">{{ $user->is_admin ? 'Yes' : 'No' }}</td>
                <td class="px-5 py-3 text-right space-x-3">
                    <a class="text-blue-600 font-semibold" href="{{ route('admin.users.edit', $user) }}">Edit</a>
                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Delete this user?')">@csrf @method('DELETE')<button class="text-red-600 font-semibold">Delete</button></form>
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="px-5 py-8 text-center text-slate-400">No users found.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
