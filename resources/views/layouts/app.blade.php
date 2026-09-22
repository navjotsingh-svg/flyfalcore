<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Falcore') — {{ $falcore['tagline'] }}</title>
    <link rel="icon" href="{{ asset('images/falcore-logo.png') }}" type="image/png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f8f3e8',
                            100: '#efe3c4',
                            400: '#d4b06a',
                            500: '#c9a45c',
                            600: '#b08b3f',
                            700: '#8c6d2e',
                            800: '#1a2d4d',
                            900: '#0b1a33',
                        },
                        ink: '#0b1a33',
                        navy: {
                            700: '#16325c',
                            800: '#102544',
                            900: '#0b1a33',
                            950: '#071222',
                        },
                        gold: {
                            400: '#d4b06a',
                            500: '#c9a45c',
                            600: '#b08b3f',
                        },
                    },
                    fontFamily: {
                        sans: ['Outfit', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        display: ['Playfair Display', 'Georgia', 'serif'],
                    },
                    boxShadow: {
                        card: '0 18px 50px -20px rgba(11, 26, 51, 0.28)',
                        search: '0 30px 60px -24px rgba(11, 26, 51, 0.32)',
                    },
                },
            },
        }
    </script>
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: Outfit, sans-serif; }
        .hero-official {
            background-color: #071222;
            background-image:
                linear-gradient(90deg, rgba(7,18,34,0.88) 0%, rgba(7,18,34,0.55) 48%, rgba(7,18,34,0.28) 100%),
                url('https://images.unsplash.com/photo-1436491865332-7a61a109cc05?auto=format&fit=crop&w=2400&q=80');
            background-size: cover;
            background-position: center 40%;
        }
        .page-hero {
            background-color: #071222;
            background-image:
                linear-gradient(90deg, rgba(7,18,34,0.86) 0%, rgba(7,18,34,0.55) 100%),
                url('https://images.unsplash.com/photo-1540962351504-03099e0a754b?auto=format&fit=crop&w=2400&q=80');
            background-size: cover;
            background-position: center;
        }
        .iata-photo {
            background-image:
                linear-gradient(90deg, rgba(7,18,34,0.92) 0%, rgba(7,18,34,0.55) 70%, rgba(7,18,34,0.25) 100%),
                url('https://images.unsplash.com/photo-1529074963764-98f45c47344b?auto=format&fit=crop&w=2400&q=80');
            background-size: cover;
            background-position: center;
        }
        .vision-photo {
            background-image:
                linear-gradient(180deg, rgba(7,18,34,0.88), rgba(7,18,34,0.78)),
                url('https://images.unsplash.com/photo-1464037866556-6812c9d1c72e?auto=format&fit=crop&w=2400&q=80');
            background-size: cover;
            background-position: center;
        }
        [x-cloak] { display: none !important; }
    </style>
    @stack('head')
</head>
<body class="bg-white text-ink antialiased min-h-screen flex flex-col" x-data="{ mobile: false, chat: false }">
    <header class="sticky top-0 z-50 bg-white border-b border-slate-100 shadow-sm">
        <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-3 lg:py-4 flex items-center justify-between gap-6">
            @include('partials.logo', ['class' => 'h-12 sm:h-14 w-auto'])

            <div class="hidden lg:flex flex-col items-end gap-2.5 min-w-0">
                <div class="flex flex-wrap items-center justify-end gap-x-6 gap-y-1 text-[12px] text-navy-800">
                    <a href="tel:{{ $falcore['phones']['usa']['href'] }}" class="inline-flex items-center gap-2 hover:text-gold-600">
                        <i class="fa-solid fa-phone text-gold-500"></i>
                        <span>{{ $falcore['phones']['usa']['label'] }} <strong>{{ $falcore['phones']['usa']['display'] }}</strong></span>
                    </a>
                    <a href="tel:{{ $falcore['phones']['india']['href'] }}" class="inline-flex items-center gap-2 hover:text-gold-600">
                        <i class="fa-solid fa-phone text-gold-500"></i>
                        <span>{{ $falcore['phones']['india']['label'] }} <strong>{{ $falcore['phones']['india']['display'] }}</strong></span>
                    </a>
                    <a href="mailto:{{ $falcore['email'] }}" class="inline-flex items-center gap-2 hover:text-gold-600">
                        <i class="fa-regular fa-envelope text-gold-500"></i>
                        <span>{{ $falcore['email'] }}</span>
                    </a>
                </div>
                <nav class="flex items-center gap-6 text-[13px] font-medium text-navy-800">
                    <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'text-gold-600' : 'hover:text-gold-600' }}">Home</a>
                    <a href="{{ route('about') }}" class="{{ request()->routeIs('about') ? 'text-gold-600' : 'hover:text-gold-600' }}">About Falcore</a>
                    <a href="{{ route('services') }}" class="{{ request()->routeIs('services') ? 'text-gold-600' : 'hover:text-gold-600' }}">Services</a>
                    <a href="{{ route('corporate') }}" class="{{ request()->routeIs('corporate') ? 'text-gold-600' : 'hover:text-gold-600' }}">Corporate Travel</a>
                    <a href="{{ route('partnerships') }}" class="{{ request()->routeIs('partnerships') ? 'text-gold-600' : 'hover:text-gold-600' }}">Partnerships</a>
                    <a href="{{ route('travelers') }}" class="{{ request()->routeIs('travelers') ? 'text-gold-600' : 'hover:text-gold-600' }}">Travelers</a>
                    <a href="{{ route('contact') }}" class="{{ request()->routeIs('contact') ? 'text-gold-600' : 'hover:text-gold-600' }}">Contact</a>
                    @auth
                        <a href="{{ route('account.bookings') }}" class="{{ request()->routeIs('account.bookings') ? 'text-gold-600' : 'hover:text-gold-600' }}">My trips</a>
                        <a href="{{ route('account.passengers') }}" class="{{ request()->routeIs('account.passengers*') ? 'text-gold-600' : 'hover:text-gold-600' }}">Passengers</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button class="hover:text-gold-600">Sign out</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="{{ request()->routeIs('login') ? 'text-gold-600' : 'hover:text-gold-600' }}">Sign in</a>
                        <a href="{{ route('signup') }}" class="inline-flex items-center rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 text-xs font-semibold px-4 py-2 transition">
                            Sign up
                        </a>
                    @endauth
                    <a href="{{ route('partnerships') }}" class="inline-flex items-center rounded-full border-2 border-gold-500 text-gold-600 hover:bg-gold-500 hover:text-navy-950 text-xs font-semibold px-4 py-2 transition">
                        Partner With Falcore
                    </a>
                </nav>
            </div>

            <button type="button" class="lg:hidden inline-flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 text-navy-900" @click="mobile = !mobile" aria-label="Open menu">
                <i class="fa-solid fa-bars"></i>
            </button>
        </div>

        <div x-cloak x-show="mobile" x-transition class="lg:hidden border-t border-slate-100 bg-white px-4 py-4 space-y-2 text-sm font-medium text-navy-900">
            <a href="{{ route('home') }}" class="block py-2">Home</a>
            <a href="{{ route('about') }}" class="block py-2">About Falcore</a>
            <a href="{{ route('services') }}" class="block py-2">Services</a>
            <a href="{{ route('corporate') }}" class="block py-2">Corporate Travel</a>
            <a href="{{ route('partnerships') }}" class="block py-2">Partnerships</a>
            <a href="{{ route('travelers') }}" class="block py-2">Travelers</a>
            <a href="{{ route('contact') }}" class="block py-2">Contact</a>
            @auth
                <a href="{{ route('account.bookings') }}" class="block py-2">My trips</a>
                <a href="{{ route('account.passengers') }}" class="block py-2">Saved passengers</a>
                <a href="{{ route('account.profile') }}" class="block py-2">Profile</a>
                <form action="{{ route('logout') }}" method="POST" class="pt-2">
                    @csrf
                    <button class="font-semibold">Sign out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="block py-2">Sign in</a>
                <a href="{{ route('signup') }}" class="inline-flex mt-2 rounded-full bg-gold-500 text-navy-950 px-4 py-2 font-semibold">Sign up</a>
            @endauth
            <a href="{{ route('partnerships') }}" class="inline-flex mt-2 rounded-full border-2 border-gold-500 text-gold-600 px-4 py-2 font-semibold">Partner With Falcore</a>
            <div class="pt-3 text-xs text-slate-500 space-y-1">
                <a class="block" href="tel:{{ $falcore['phones']['usa']['href'] }}">{{ $falcore['phones']['usa']['display'] }}</a>
                <a class="block" href="tel:{{ $falcore['phones']['india']['href'] }}">{{ $falcore['phones']['india']['display'] }}</a>
                <a class="block" href="mailto:{{ $falcore['email'] }}">{{ $falcore['email'] }}</a>
            </div>
        </div>
    </header>

    @if(session('success'))
        <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 pt-4 w-full">
            <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 pt-4 w-full">
            <div class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                {{ session('error') }}
            </div>
        </div>
    @endif

    <main class="flex-1">
        @yield('content')
    </main>

    <footer class="bg-navy-950 text-white mt-auto">
        <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-14 grid lg:grid-cols-12 gap-10">
            <div class="lg:col-span-4">
                @include('partials.logo', ['class' => 'h-14 w-auto'])
                <p class="mt-5 text-sm text-white/65 max-w-sm leading-relaxed">
                    Global travel solutions. Aviation expertise. Trusted partnerships. All at the core of every journey.
                </p>
                <div class="mt-6 flex items-center gap-3">
                    <span class="h-9 w-9 rounded-full border border-white/20 inline-flex items-center justify-center text-gold-400"><i class="fa-brands fa-facebook-f"></i></span>
                    <span class="h-9 w-9 rounded-full border border-white/20 inline-flex items-center justify-center text-gold-400"><i class="fa-brands fa-linkedin-in"></i></span>
                    <span class="h-9 w-9 rounded-full border border-white/20 inline-flex items-center justify-center text-gold-400"><i class="fa-brands fa-instagram"></i></span>
                    <span class="h-9 w-9 rounded-full border border-white/20 inline-flex items-center justify-center text-gold-400"><i class="fa-brands fa-youtube"></i></span>
                </div>
            </div>
            <div class="lg:col-span-8 grid sm:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-xs font-bold tracking-[0.18em] text-gold-400">COMPANY</h3>
                    <ul class="mt-4 space-y-2 text-sm text-white/70">
                        <li><a class="hover:text-gold-400" href="{{ route('about') }}">About Falcore</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('services') }}">Our Services</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('corporate') }}">Corporate Travel</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('partnerships') }}">Global Partnerships</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('travelers') }}">Travelers</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('contact') }}">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold tracking-[0.18em] text-gold-400">SERVICES</h3>
                    <ul class="mt-4 space-y-2 text-sm text-white/70">
                        <li><a class="hover:text-gold-400" href="{{ route('flights.index') }}">Air Travel Services</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('flights.index') }}">International Travel</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('corporate') }}">Corporate Travel</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('services') }}">Leisure Travel</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('services') }}">Group Travel</a></li>
                        <li><a class="hover:text-gold-400" href="{{ route('partnerships') }}">Travel Industry Services</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-xs font-bold tracking-[0.18em] text-gold-400">NEWSLETTER</h3>
                    <div class="mt-4 space-y-4 text-sm">
                        <div>
                            <p class="font-semibold">Falcore</p>
                            <p class="text-white/60">Travel notes by email, after a one-time code.</p>
                        </div>
                        @if($pendingNewsletterOtp ?? null)
                            @include('partials.otp-verify', ['pendingOtp' => $pendingNewsletterOtp])
                        @else
                            <form action="{{ route('newsletter.store') }}" method="POST" class="space-y-2">
                                @csrf
                                <input type="email" name="email" required placeholder="you@email.com"
                                       class="w-full rounded-xl border border-white/15 bg-white/5 px-3 py-2.5 text-sm text-white placeholder:text-white/40">
                                @error('email') <p class="text-red-300 text-xs">{{ $message }}</p> @enderror
                                <button class="w-full rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 text-xs font-semibold py-2.5">Send code</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-[1320px] mx-auto px-4 sm:px-6 lg:px-8 py-5 flex flex-col md:flex-row items-center justify-between gap-3 text-xs text-white/50">
                <p>© {{ date('Y') }} {{ $falcore['company'] }}. All Rights Reserved.</p>
                <div class="flex items-center gap-4">
                    <a class="hover:text-white" href="{{ route('about') }}">Privacy Policy</a>
                    <a class="hover:text-white" href="{{ route('about') }}">Terms &amp; Conditions</a>
                    <a class="hover:text-white" href="{{ route('contact') }}">Cookie Policy</a>
                </div>
            </div>
        </div>
    </footer>

    <button type="button"
            @click="chat = !chat"
            class="fixed right-0 top-1/2 -translate-y-1/2 z-40 hidden md:flex items-center rounded-l-xl bg-gold-500 hover:bg-gold-400 text-navy-950 text-xs font-semibold tracking-wide px-2 py-8 shadow-lg">
        <span style="writing-mode: vertical-rl; transform: rotate(180deg);">Chat with us</span>
    </button>

    <div x-cloak x-show="chat" x-transition
         class="fixed bottom-6 right-6 z-50 w-[320px] rounded-2xl bg-white shadow-card border border-slate-100 overflow-hidden">
        <div class="bg-navy-900 text-white px-4 py-3 flex items-center justify-between">
            <p class="text-sm font-semibold">Falcore concierge</p>
            <button type="button" @click="chat = false" class="text-white/80 hover:text-white">✕</button>
        </div>
        <div class="p-4 text-sm text-slate-600 space-y-3">
            <p>Need help finding a fare or managing a booking? Call or write to the Falcore team.</p>
            <a href="tel:{{ $falcore['phones']['usa']['href'] }}" class="block font-semibold text-navy-900">{{ $falcore['phones']['usa']['display'] }}</a>
            <a href="tel:{{ $falcore['phones']['india']['href'] }}" class="block font-semibold text-navy-900">{{ $falcore['phones']['india']['display'] }}</a>
            <a href="mailto:{{ $falcore['email'] }}" class="block font-semibold text-navy-900">{{ $falcore['email'] }}</a>
            <a href="{{ route('contact') }}" class="inline-flex rounded-full bg-gold-500 text-navy-950 font-semibold px-4 py-2">Send a message</a>
            <a href="{{ route('bookings.lookup') }}" class="block text-gold-600 font-medium">Look up a booking →</a>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('airportPair', (initial = {}) => ({
                url: initial.url || '/airports/suggest',
                fromCode: initial.fromCode || '',
                fromQuery: initial.fromQuery || '',
                fromItems: [],
                fromOpen: false,
                fromActive: -1,
                fromError: false,
                toCode: initial.toCode || '',
                toQuery: initial.toQuery || '',
                toItems: [],
                toOpen: false,
                toActive: -1,
                toError: false,
                async search(side) {
                    this[side + 'Code'] = '';
                    this[side + 'Error'] = false;
                    this[side + 'Active'] = 0;
                    const query = (this[side + 'Query'] || '').trim();
                    if (query.length < 2) {
                        this[side + 'Items'] = [];
                        this[side + 'Open'] = false;
                        return;
                    }
                    try {
                        const response = await fetch(this.url + '?q=' + encodeURIComponent(query), {
                            headers: { Accept: 'application/json' },
                        });
                        const payload = await response.json();
                        this[side + 'Items'] = payload.data || [];
                    } catch (error) {
                        this[side + 'Items'] = [];
                    }
                    this[side + 'Open'] = this[side + 'Items'].length > 0;
                },
                open(side) {
                    if (this[side + 'Items'].length) {
                        this[side + 'Open'] = true;
                    }
                },
                close(side) {
                    this[side + 'Open'] = false;
                },
                move(side, step) {
                    const items = this[side + 'Items'];
                    if (!items.length) return;
                    this[side + 'Open'] = true;
                    const next = this[side + 'Active'] + step;
                    this[side + 'Active'] = (next + items.length) % items.length;
                },
                chooseActive(side) {
                    const item = this[side + 'Items'][this[side + 'Active']];
                    if (item) this.select(side, item);
                },
                select(side, item) {
                    this[side + 'Code'] = item.code;
                    this[side + 'Query'] = item.label;
                    this[side + 'Items'] = [];
                    this[side + 'Open'] = false;
                    this[side + 'Error'] = false;
                },
                swap() {
                    const code = this.fromCode;
                    const query = this.fromQuery;
                    this.fromCode = this.toCode;
                    this.fromQuery = this.toQuery;
                    this.toCode = code;
                    this.toQuery = query;
                    this.fromItems = [];
                    this.toItems = [];
                    this.fromOpen = false;
                    this.toOpen = false;
                },
                acceptTyped(side) {
                    const typed = (this[side + 'Query'] || '').trim().toUpperCase();
                    if (this[side + 'Code']) return true;
                    if (/^[A-Z]{3}$/.test(typed)) {
                        this[side + 'Code'] = typed;
                        return true;
                    }
                    this[side + 'Error'] = true;
                    return false;
                },
                validate() {
                    return this.acceptTyped('from') && this.acceptTyped('to');
                },
            }));
            Alpine.data('travellerMix', (initial = {}) => ({
                open: false,
                adults: Math.max(1, parseInt(initial.adults || 1, 10)),
                children: Math.max(0, parseInt(initial.children || 0, 10)),
                infants: Math.max(0, parseInt(initial.infants || 0, 10)),
                seated() {
                    return this.adults + this.children;
                },
                total() {
                    return this.adults + this.children + this.infants;
                },
                summary() {
                    const parts = [this.adults + (this.adults === 1 ? ' Adult' : ' Adults')];
                    if (this.children) parts.push(this.children + (this.children === 1 ? ' Child' : ' Children'));
                    if (this.infants) parts.push(this.infants + (this.infants === 1 ? ' Infant' : ' Infants'));
                    return parts.join(', ');
                },
                inc(key) {
                    if (key === 'adults' && this.total() < 9) this.adults += 1;
                    if (key === 'children' && this.seated() < 9 && this.total() < 9) this.children += 1;
                    if (key === 'infants' && this.infants < this.adults && this.total() < 9) this.infants += 1;
                },
                dec(key) {
                    if (key === 'adults' && this.adults > 1) {
                        this.adults -= 1;
                        if (this.infants > this.adults) this.infants = this.adults;
                    }
                    if (key === 'children' && this.children > 0) this.children -= 1;
                    if (key === 'infants' && this.infants > 0) this.infants -= 1;
                },
            }));
        });
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js"></script>
    @stack('scripts')
</body>
</html>
