<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin sign in — Falcore</title>
    <link rel="icon" href="{{ asset('images/falcore-logo.png') }}" type="image/png">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white flex items-center justify-center px-4" style="font-family:'Plus Jakarta Sans',sans-serif">
    <div class="w-full max-w-md">
        <img src="{{ asset('images/falcore-logo.png') }}" alt="Falcore" class="h-12 w-auto mx-auto mb-8">
        <div class="rounded-3xl bg-white text-slate-900 p-8 shadow-2xl">
            <h1 class="text-2xl font-extrabold">Admin sign in</h1>
            <p class="text-sm text-slate-500 mt-1">Manage bookings, flights, and catalog records.</p>
            <form action="{{ route('admin.login.store') }}" method="POST" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-semibold text-slate-500">Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                    @error('email') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Password</label>
                    <input type="password" name="password" required class="mt-1 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" value="1"> Remember me
                </label>
                <button type="submit" class="w-full rounded-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5">Sign in</button>
            </form>
        </div>
    </div>
</body>
</html>
