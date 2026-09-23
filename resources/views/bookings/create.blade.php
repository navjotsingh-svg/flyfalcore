@extends('layouts.app')

@section('title', 'Book Flight')

@section('content')
@php
    $isLiveFare = ($mode ?? 'local') === 'duffel';
    $symbols = ['USD' => '$', 'GBP' => '£', 'EUR' => '€', 'INR' => '₹'];
    $symbol = $symbols[$currency] ?? $currency.' ';
    $legs = $legs ?? ($offer['legs'] ?? []);
    $routeLabel = $isLiveFare
        ? $offer['origin_code'].' → '.$offer['destination_code'].' · '.$offer['departure_at']->format('D, M j · H:i').' · '.$offer['airline']
        : $flight->originAirport->code.' → '.$flight->destinationAirport->code.' · '.$flight->departure_at->format('D, M j · H:i').' · '.$flight->airline->name;
    if (count($legs) > 1) {
        $routeLabel = collect($legs)->map(function ($leg) {
            $depart = $leg['departure_at'] instanceof \Carbon\CarbonInterface
                ? $leg['departure_at']
                : \Carbon\Carbon::parse($leg['departure_at']);

            return ($leg['label'] ?? 'Flight').' '.$leg['origin_code'].' → '.$leg['destination_code'].' · '.$depart->format('D, M j · H:i');
        })->implode(' · ');
    }
    $savedPassengers = $savedPassengers ?? [];
    $passengerSlots = $passengerSlots ?? array_fill(0, $passengers, ['type' => 'adult', 'label' => 'Adult', 'hint' => '12+ years on travel date']);
    $mix = $mix ?? null;
    $ancillaries = $ancillaries ?? ['bags' => [], 'seats' => [], 'cabin_label' => 'Economy'];
    $cabinLabel = $ancillaries['cabin_label'] ?? \App\Support\AirlineCopy::cabinLabel($offer['cabin_class'] ?? $flight?->cabin_class ?? 'economy');
@endphp
<section class="max-w-3xl mx-auto px-4 sm:px-6 py-10"
         x-data="passengerCheckout({
              saved: {{ \Illuminate\Support\Js::from($savedPassengers) }},
              count: {{ (int) $passengers }},
              old: {{ \Illuminate\Support\Js::from(old('passengers', [])) }},
              slots: {{ \Illuminate\Support\Js::from($passengerSlots) }},
              travelDate: {{ \Illuminate\Support\Js::from($travelDate ?? now()->toDateString()) }},
              requiresIdentity: {{ $isLiveFare ? 'true' : 'false' }},
              ancillaries: {{ \Illuminate\Support\Js::from($ancillaries) }},
              extrasOld: {{ \Illuminate\Support\Js::from(old('extras', [])) }},
              fareTotal: {{ (float) $total }},
              symbol: {{ \Illuminate\Support\Js::from($symbol) }}
          })">
    <h1 class="text-3xl font-extrabold">Passenger details</h1>
    <p class="mt-2 text-slate-500">{{ $routeLabel }} · <span class="capitalize font-semibold text-navy-900">{{ $cabinLabel }}</span></p>

    <div class="mt-4 rounded-xl bg-slate-50 border border-slate-100 px-4 py-3 text-sm flex flex-wrap justify-between gap-2">
        <span>{{ $mix?->summary() ?? ($passengers.' passenger'.($passengers > 1 ? 's' : '')) }} · Fare <strong>{{ $symbol }}{{ number_format($total, 2) }}</strong></span>
        <span class="text-gold-600 font-semibold" x-text="'Total ' + formatMoney(grandTotal())">Secure checkout</span>
    </div>

    <form action="{{ $formAction }}" method="POST" class="mt-8 space-y-8">
        @csrf
        @if($mix)
            <input type="hidden" name="adults" value="{{ $mix->adults }}">
            <input type="hidden" name="children" value="{{ $mix->children }}">
            <input type="hidden" name="infants" value="{{ $mix->infants }}">
        @endif
        @if(!empty($returnFlight))
            <input type="hidden" name="return_flight" value="{{ $returnFlight->id }}">
        @endif

        <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
            <h2 class="font-semibold text-lg">Contact details</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium mb-1">Full name</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', auth()->user()?->name) }}" required
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                    @error('contact_name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="contact_email" value="{{ old('contact_email', auth()->user()?->email) }}" required
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                    @error('contact_email') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">Phone {{ $isLiveFare ? '(international)' : '' }}</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" {{ $isLiveFare ? 'required' : '' }}
                           placeholder="+9198XXXXXXXX" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                    @error('contact_phone') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        @auth
            <div class="rounded-2xl border border-gold-500/30 bg-navy-950 text-white p-5 sm:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold tracking-[0.16em] uppercase text-gold-400">Saved passengers</p>
                        <h2 class="mt-1 font-semibold text-lg">Choose from passengers you have added before</h2>
                        <p class="mt-1 text-sm text-white/65">Tap a name on a traveller card to fill their details, like RedBus.</p>
                    </div>
                    <a href="{{ route('account.passengers') }}" class="text-sm font-semibold text-gold-400 hover:text-gold-300">Manage list →</a>
                </div>
                <p class="mt-3 text-sm text-white/70" x-show="saved.length === 0">No saved passengers yet. Details you enter below will be saved to your account.</p>
            </div>
        @else
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-600">
                <a href="{{ route('login') }}" class="font-semibold text-navy-900 hover:text-gold-600">Sign in</a>
                to pick from passengers you have already added.
            </div>
        @endauth

        @foreach($passengerSlots as $i => $slot)
            <div class="rounded-2xl border border-slate-200 bg-white p-6 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="font-semibold text-lg">Passenger {{ $i + 1 }} · {{ $slot['label'] }}</h2>
                        <p class="text-sm text-slate-500">{{ $slot['hint'] }}</p>
                    </div>
                    <button type="button" class="text-sm font-semibold text-slate-500 hover:text-navy-900" @click="clearSlot({{ $i }})">Clear</button>
                </div>
                <input type="hidden" name="passengers[{{ $i }}][type]" value="{{ $slot['type'] }}">

                <div class="flex flex-wrap gap-2" x-show="savedForSlot({{ $i }}).length > 0">
                    <template x-for="person in savedForSlot({{ $i }})" :key="person.id">
                        <button type="button"
                                class="rounded-full border px-3 py-1.5 text-sm font-semibold transition"
                                :class="isSelected({{ $i }}, person.id)
                                    ? 'bg-gold-500 text-navy-950 border-gold-500'
                                    : (isUsed(person.id, {{ $i }}) ? 'border-slate-200 text-slate-300 cursor-not-allowed' : 'border-slate-200 text-navy-800 hover:border-gold-500')"
                                :disabled="isUsed(person.id, {{ $i }})"
                                @click="applySaved({{ $i }}, person)"
                                x-text="person.label">
                        </button>
                    </template>
                    <button type="button"
                            class="rounded-full border border-dashed border-slate-300 px-3 py-1.5 text-sm font-semibold text-slate-500 hover:border-gold-500 hover:text-navy-900"
                            @click="clearSlot({{ $i }})">
                        + Add new
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Title</label>
                        <select name="passengers[{{ $i }}][title]" {{ $isLiveFare ? 'required' : '' }}
                                x-model="passengers[{{ $i }}].title"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                            @unless($isLiveFare)<option value="">Optional</option>@endunless
                            <option value="mr">Mr</option>
                            <option value="mrs">Mrs</option>
                            <option value="ms">Ms</option>
                            <option value="miss">Miss</option>
                            <option value="dr">Dr</option>
                        </select>
                    </div>
                    <div class="hidden sm:block"></div>
                    <div>
                        <label class="block text-sm font-medium mb-1">First name</label>
                        <input type="text" name="passengers[{{ $i }}][first_name]" required
                               x-model="passengers[{{ $i }}].first_name"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                        @error("passengers.$i.first_name") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Last name</label>
                        <input type="text" name="passengers[{{ $i }}][last_name]" required
                               x-model="passengers[{{ $i }}].last_name"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                        @error("passengers.$i.last_name") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="sm:col-span-2">
                        @include('partials.dob-selects', [
                            'dayName' => "passengers[$i][dob_day]",
                            'monthName' => "passengers[$i][dob_month]",
                            'yearName' => "passengers[$i][dob_year]",
                            'hiddenName' => "passengers[$i][date_of_birth]",
                            'dayOld' => "passengers.$i.dob_day",
                            'monthOld' => "passengers.$i.dob_month",
                            'yearOld' => "passengers.$i.dob_year",
                            'hiddenOld' => "passengers.$i.date_of_birth",
                            'value' => old("passengers.$i.date_of_birth"),
                            'required' => $isLiveFare,
                            'alpineIndex' => $i,
                            'passengerType' => $slot['type'],
                            'travelDate' => $travelDate ?? null,
                            'hint' => 'Must match the passport, as DD/MM/YYYY. '.$slot['hint'].'.',
                        ])
                        @error("passengers.$i.date_of_birth") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        @error("passengers.$i.dob_day") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        @error("passengers.$i.dob_month") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                        @error("passengers.$i.dob_year") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Gender</label>
                        <select name="passengers[{{ $i }}][gender]" {{ $isLiveFare ? 'required' : '' }}
                                x-model="passengers[{{ $i }}].gender"
                                class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                            @unless($isLiveFare)<option value="">Optional</option>@endunless
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                        @error("passengers.$i.gender") <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Passport number</label>
                        <input type="text" name="passengers[{{ $i }}][passport_number]"
                               x-model="passengers[{{ $i }}].passport_number"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Nationality</label>
                        <input type="text" name="passengers[{{ $i }}][nationality]"
                               x-model="passengers[{{ $i }}].nationality"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50">
                    </div>
                    @auth
                        <div class="sm:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                                <input type="hidden" name="passengers[{{ $i }}][save]" value="0">
                                <input type="checkbox" name="passengers[{{ $i }}][save]" value="1" checked class="rounded border-slate-300 text-gold-500 focus:ring-gold-500">
                                Save this passenger for next time
                            </label>
                        </div>
                    @endauth
                </div>
            </div>
        @endforeach

        @include('partials.booking-extras')

        <div class="rounded-2xl border border-slate-200 bg-white p-5 flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm text-slate-500">Payable now</p>
                <p class="text-2xl font-extrabold text-navy-900" x-text="formatMoney(grandTotal())">{{ $symbol }}{{ number_format($total, 2) }}</p>
            </div>
            <button type="submit" class="w-full sm:w-auto rounded-full bg-gold-500 hover:bg-gold-400 text-navy-950 font-semibold px-8 py-3 transition">
                Continue to pay
            </button>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
function passengerCheckout({ saved, count, old, slots, travelDate, requiresIdentity, ancillaries, extrasOld, fareTotal, symbol }) {
    const empty = () => ({
        saved_id: '',
        title: requiresIdentity ? 'mr' : '',
        first_name: '',
        last_name: '',
        dob_day: '',
        dob_month: '',
        dob_year: '',
        date_of_birth: '',
        gender: requiresIdentity ? 'male' : '',
        passport_number: '',
        nationality: '',
    });

    const ageYears = (dob) => {
        if (!dob) return null;
        const birth = new Date(dob);
        const on = travelDate ? new Date(travelDate) : new Date();
        if (Number.isNaN(birth.getTime()) || Number.isNaN(on.getTime())) return null;
        let age = on.getFullYear() - birth.getFullYear();
        const month = on.getMonth() - birth.getMonth();
        if (month < 0 || (month === 0 && on.getDate() < birth.getDate())) age -= 1;
        return age;
    };

    const typeForAge = (age) => {
        if (age === null) return null;
        if (age < 2) return 'infant_without_seat';
        if (age < 12) return 'child';
        return 'adult';
    };

    const splitDob = (row) => {
        const next = { ...empty(), ...row };
        let day = String(row.dob_day || '');
        let month = String(row.dob_month || '');
        let year = String(row.dob_year || '');
        const dob = String(row.date_of_birth || '');

        if (dob.includes('-')) {
            const [y, m, d] = dob.split('-');
            year = year || y;
            month = month || m;
            day = day || d;
        } else if (dob.includes('/')) {
            const [d, m, y] = dob.split('/');
            day = day || d;
            month = month || m;
            year = year || y;
        }

        next.dob_day = day ? String(day).padStart(2, '0') : '';
        next.dob_month = month ? String(month).padStart(2, '0') : '';
        next.dob_year = year || '';
        if (next.dob_day && next.dob_month && next.dob_year) {
            next.date_of_birth = `${next.dob_year}-${next.dob_month}-${next.dob_day}`;
        }

        return next;
    };

    const extras = extrasOld || {};
    const bagOptions = (ancillaries && ancillaries.bags) || {};

    return {
        saved: saved || [],
        slots: slots || [],
        ancillaries: ancillaries || { seats: [], bags: {} },
        symbol: symbol || '',
        fareTotal: Number(fareTotal || 0),
        selecting: 0,
        bags: Array.from({ length: count }, (_, index) => Number((extras.bags && extras.bags[index]) || 0)),
        seats: Array.from({ length: count }, (_, index) => String((extras.seats && extras.seats[index]) || '')),
        passengers: Array.from({ length: count }, (_, index) => old[index] ? splitDob(old[index]) : empty()),
        savedForSlot(index) {
            const slotType = (this.slots[index] && this.slots[index].type) || 'adult';
            return this.saved.filter((person) => {
                const type = typeForAge(ageYears(person.date_of_birth));
                return !type || type === slotType;
            });
        },
        isSelected(index, id) {
            return String(this.passengers[index].saved_id) === String(id);
        },
        isUsed(id, exceptIndex) {
            return this.passengers.some((passenger, index) => index !== exceptIndex && String(passenger.saved_id) === String(id));
        },
        applySaved(index, person) {
            if (this.isUsed(person.id, index)) return;
            this.passengers[index] = splitDob({ ...person, saved_id: person.id });
        },
        clearSlot(index) {
            this.passengers[index] = empty();
        },
        syncDob(index) {
            const passenger = this.passengers[index];
            if (passenger.dob_day && passenger.dob_month && passenger.dob_year) {
                passenger.date_of_birth = `${passenger.dob_year}-${passenger.dob_month}-${passenger.dob_day}`;
            } else {
                passenger.date_of_birth = '';
            }
        },
        seatMeta(designator) {
            return (this.ancillaries.seats || []).find((seat) => seat.designator === designator);
        },
        pickSeat(designator) {
            const seat = this.seatMeta(designator);
            if (!seat || !seat.available) return;
            if (this.seats[this.selecting] === designator) {
                this.seats[this.selecting] = '';
                return;
            }
            this.seats = this.seats.map((value) => (value === designator ? '' : value));
            this.seats[this.selecting] = designator;
        },
        seatClass(designator, available) {
            if (!available) return 'bg-white/15 text-white/30 cursor-not-allowed';
            if (this.seats.includes(designator)) return 'bg-gold-500 text-navy-950';
            return 'bg-white text-navy-900 hover:bg-gold-400';
        },
        extrasTotal() {
            let total = 0;
            this.bags.forEach((qty, index) => {
                const option = bagOptions[index] || bagOptions[String(index)];
                if (option && Number(qty) > 0) total += Number(qty) * Number(option.amount || 0);
            });
            this.seats.forEach((designator) => {
                const seat = this.seatMeta(designator);
                if (seat) total += Number(seat.amount || 0);
            });
            return total;
        },
        grandTotal() {
            return this.fareTotal + this.extrasTotal();
        },
        formatMoney(value) {
            return this.symbol + Number(value || 0).toFixed(2);
        },
    };
}
</script>
@endpush
