@extends('layouts.admin')
@section('title', $user->exists ? 'Edit user' : 'Add user')
@section('heading', $user->exists ? 'Edit user' : 'Add user')
@section('content')
<form action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" method="POST" class="max-w-xl rounded-2xl bg-white border border-slate-200 p-6 space-y-4">
    @csrf
    @if($user->exists) @method('PUT') @endif
    <div>
        <label class="text-xs font-semibold text-slate-500">Name</label>
        <input name="name" value="{{ old('name', $user->name) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        @error('name') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Password {{ $user->exists ? '(leave blank to keep)' : '' }}</label>
        <input type="password" name="password" {{ $user->exists ? '' : 'required' }} class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
        @error('password') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin))> Admin access</label>
    <div class="flex gap-3">
        <button class="rounded-full bg-blue-600 text-white font-semibold px-5 py-2.5 text-sm">Save</button>
        <a href="{{ route('admin.users.index') }}" class="rounded-full border border-slate-200 px-5 py-2.5 text-sm">Cancel</a>
    </div>
</form>
@endsection
