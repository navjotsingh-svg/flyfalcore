<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') — Falcore Admin</title>
    <link rel="icon" href="{{ asset('images/falcore-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { 50:'#eef5ff', 500:'#2563eb', 600:'#1d4ed8', 700:'#1e40af', 800:'#1e3a8a', 900:'#0f172a' }
                    },
                    fontFamily: { sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'sans-serif'] }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 text-slate-900 font-sans min-h-screen">
    <div class="min-h-screen lg:grid lg:grid-cols-[240px_1fr]">
        <aside class="bg-slate-950 text-white">
            <div class="px-5 py-5 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="block">
                    <img src="{{ asset('images/falcore-logo.png') }}" alt="Falcore" class="h-10 w-auto">
                </a>
                <p class="mt-3 text-xs uppercase tracking-widest text-white/50">Admin</p>
            </div>
            <nav class="px-3 py-4 space-y-1 text-sm">
                @php
                    $links = [
                        ['Dashboard', 'admin.dashboard', request()->routeIs('admin.dashboard')],
                        ['Bookings', 'admin.bookings.index', request()->routeIs('admin.bookings.*')],
                        ['Flights', 'admin.flights.index', request()->routeIs('admin.flights.*')],
                        ['Passengers', 'admin.passengers.index', request()->routeIs('admin.passengers.*')],
                        ['Airlines', 'admin.airlines.index', request()->routeIs('admin.airlines.*')],
                        ['Airports', 'admin.airports.index', request()->routeIs('admin.airports.*')],
                        ['Messages', 'admin.messages.index', request()->routeIs('admin.messages.*')],
                        ['Newsletter', 'admin.subscribers.index', request()->routeIs('admin.subscribers.*')],
                        ['Users', 'admin.users.index', request()->routeIs('admin.users.*')],
                    ];
                @endphp
                @foreach($links as [$label, $route, $active])
                    <a href="{{ route($route) }}" class="block rounded-xl px-3 py-2 {{ $active ? 'bg-white/10 text-white' : 'text-white/70 hover:bg-white/5 hover:text-white' }}">{{ $label }}</a>
                @endforeach
            </nav>
            <div class="px-5 py-4 border-t border-white/10 text-xs text-white/50">
                <p>{{ auth()->user()->name }}</p>
                <a href="{{ route('home') }}" class="text-sky-300 hover:text-white">View site</a>
            </div>
        </aside>

        <div class="min-w-0">
            <header class="bg-white border-b border-slate-200 px-4 sm:px-8 py-4 flex items-center justify-between gap-4">
                <h1 class="text-lg font-extrabold">@yield('heading', 'Dashboard')</h1>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button class="text-sm font-semibold text-slate-500 hover:text-slate-900">Sign out</button>
                </form>
            </header>

            <div class="px-4 sm:px-8 py-6">
                @if(session('success'))
                    <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="mb-4 rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ session('error') }}</div>
                @endif
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
