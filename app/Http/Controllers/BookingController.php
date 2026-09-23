<?php

namespace App\Http\Controllers;

use App\Exceptions\DuffelException;
use App\Exceptions\PayPalException;
use App\Models\Booking;
use App\Models\Flight;
use App\Services\Bookings\BookingCheckoutService;
use App\Services\Duffel\DuffelClient;
use App\Services\Flights\FlightSearchService;
use App\Services\PayPal\PayPalClient;
use App\Support\AncillaryCatalog;
use App\Support\DateOfBirth;
use App\Support\PassengerMix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(
        protected BookingCheckoutService $checkout,
        protected PayPalClient $paypal,
        protected DuffelClient $duffel,
        protected FlightSearchService $search,
    ) {}

    public function create(Request $request, Flight $flight): View
    {
        $flight->load(['airline', 'originAirport', 'destinationAirport']);
        $mix = PassengerMix::fromRequest($request);
        $seated = $mix->seatedCount();

        abort_if($flight->available_seats < $seated, 422, 'Not enough seats available.');
        abort_if($flight->status !== 'scheduled' || $flight->departure_at->isPast(), 422, 'This flight is not available for booking.');

        $returnFlight = $this->resolveReturnFlight($request, $flight);
        if ($returnFlight && $returnFlight->available_seats < $seated) {
            abort(422, 'Not enough seats available on the return flight.');
        }

        $offer = $this->search->presentLocalOffer($flight, $returnFlight);
        $passengerSlots = $mix->slots();
        $ancillaries = AncillaryCatalog::forLocal($flight, $passengerSlots, 'INR');

        return view('bookings.create', [
            'mode' => 'local',
            'flight' => $flight,
            'returnFlight' => $returnFlight,
            'offer' => $offer,
            'legs' => $offer['legs'],
            'passengers' => $mix->totalCount(),
            'passengerSlots' => $passengerSlots,
            'mix' => $mix,
            'travelDate' => $flight->departure_at->toDateString(),
            'total' => $offer['price'] * $seated,
            'currency' => 'INR',
            'ancillaries' => $ancillaries,
            'formAction' => route('bookings.store', array_filter([
                'flight' => $flight,
                'return_flight' => $returnFlight?->id,
            ])),
            'paypalReady' => $this->paypal->configured(),
            'savedPassengers' => $this->savedPassengersForCheckout(),
        ]);
    }

    public function createFromOffer(string $offer): View|RedirectResponse
    {
        try {
            $offerData = $this->search->presentDuffelOffer($this->duffel->getOffer($offer));
        } catch (DuffelException $exception) {
            return redirect()->route('flights.index')->with('error', $exception->getMessage());
        }

        $passengerSlots = $this->slotsFromOffer($offerData['passengers'] ?? []);
        $passengers = max(1, count($passengerSlots));
        $ancillaries = AncillaryCatalog::forLiveOffer(
            $offerData,
            $this->duffel->getSeatMaps($offer),
            $passengerSlots,
        );

        return view('bookings.create', [
            'mode' => 'duffel',
            'flight' => null,
            'returnFlight' => null,
            'offer' => $offerData,
            'legs' => $offerData['legs'] ?? [],
            'passengers' => $passengers,
            'passengerSlots' => $passengerSlots,
            'mix' => PassengerMix::fromTypes(array_column($passengerSlots, 'type')),
            'travelDate' => $offerData['departure_at']->toDateString(),
            'total' => $offerData['price'],
            'currency' => $offerData['currency'],
            'ancillaries' => $ancillaries,
            'formAction' => route('offers.store', $offer),
            'paypalReady' => $this->paypal->configured(),
            'savedPassengers' => $this->savedPassengersForCheckout(),
        ]);
    }

    public function store(Request $request, Flight $flight): RedirectResponse
    {
        $mix = PassengerMix::fromRequest($request);
        $returnFlight = $this->resolveReturnFlight($request, $flight);
        $validated = $this->validateBooking($request, duffel: false, expectedTypes: $mix->types(), travelDate: $flight->departure_at);
        $count = $mix->seatedCount();
        $slots = $mix->slots();
        $ancillaries = AncillaryCatalog::forLocal($flight, $slots, 'INR');
        $extras = AncillaryCatalog::resolve($ancillaries, $validated['extras'] ?? [], $slots);

        abort_if($flight->available_seats < $count, 422, 'Not enough seats available.');
        abort_if($flight->status !== 'scheduled' || $flight->departure_at->isPast(), 422, 'This flight is not available for booking.');
        abort_if($returnFlight && $returnFlight->available_seats < $count, 422, 'Not enough seats available on the return flight.');

        $booking = DB::transaction(function () use ($validated, $flight, $returnFlight, $count, $mix, $extras) {
            $lockedFlight = Flight::query()->lockForUpdate()->findOrFail($flight->id);
            $lockedFlight->load(['airline', 'originAirport', 'destinationAirport']);
            $lockedReturn = $returnFlight
                ? Flight::query()->lockForUpdate()->findOrFail($returnFlight->id)->load(['airline', 'originAirport', 'destinationAirport'])
                : null;

            if ($lockedFlight->available_seats < $count) {
                abort(422, 'Not enough seats available.');
            }

            if ($lockedReturn && $lockedReturn->available_seats < $count) {
                abort(422, 'Not enough seats available on the return flight.');
            }

            $presented = $this->search->presentLocalOffer($lockedFlight, $lockedReturn);

            $booking = Booking::create([
                'source' => 'local',
                'user_id' => auth()->id(),
                'flight_id' => $lockedFlight->id,
                'contact_name' => $validated['contact_name'],
                'contact_email' => $validated['contact_email'],
                'contact_phone' => $validated['contact_phone'] ?? null,
                'passengers_count' => $mix->totalCount(),
                'total_amount' => round(($presented['price'] * $count) + $extras['total'], 2),
                'currency' => 'INR',
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'itinerary' => $this->itineraryFromPresented($presented, $extras),
            ]);

            foreach ($validated['passengers'] as $index => $passenger) {
                $payload = DateOfBirth::bookingPayload($passenger);
                $payload['seat_number'] = $extras['seats'][$index] ?? null;
                $payload['extra_bags'] = $extras['bags'][$index] ?? 0;
                $booking->passengers()->create($payload);
            }

            $lockedFlight->decrement('available_seats', $count);
            $lockedReturn?->decrement('available_seats', $count);

            return $booking;
        });

        $this->rememberPassengers($validated['passengers']);

        return $this->redirectToPayment($booking);
    }

    public function storeFromOffer(Request $request, string $offer): RedirectResponse
    {
        try {
            $offerData = $this->search->presentDuffelOffer($this->duffel->getOffer($offer));
        } catch (DuffelException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $offerPassengers = $offerData['passengers'] ?? [];
        $expectedTypes = array_map(
            fn ($passenger) => PassengerMix::normaliseType($passenger['type'] ?? 'adult'),
            $offerPassengers,
        );
        $validated = $this->validateBooking(
            $request,
            duffel: true,
            expectedTypes: $expectedTypes,
            travelDate: $offerData['departure_at'],
        );

        if (count($validated['passengers']) !== count($offerPassengers)) {
            return back()->withInput()->with('error', 'Passenger count must match the selected fare.');
        }

        $passengerSlots = $this->slotsFromOffer($offerPassengers);
        $ancillaries = AncillaryCatalog::forLiveOffer(
            $offerData,
            $this->duffel->getSeatMaps($offer),
            $passengerSlots,
        );
        $extras = AncillaryCatalog::resolve($ancillaries, $validated['extras'] ?? [], $passengerSlots);

        $booking = Booking::create([
            'source' => 'duffel',
            'user_id' => auth()->id(),
            'duffel_offer_id' => $offerData['id'],
            'duffel_offer_request_id' => $offerData['offer_request_id'],
            'contact_name' => $validated['contact_name'],
            'contact_email' => $validated['contact_email'],
            'contact_phone' => $validated['contact_phone'],
            'passengers_count' => count($validated['passengers']),
            'total_amount' => round(((float) $offerData['price']) + $extras['total'], 2),
            'currency' => $offerData['currency'],
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'itinerary' => $this->itineraryFromPresented($offerData, $extras),
        ]);

        foreach ($validated['passengers'] as $index => $passenger) {
            $payload = DateOfBirth::bookingPayload($passenger);
            $payload['type'] = $expectedTypes[$index] ?? 'adult';
            $payload['duffel_passenger_id'] = $offerPassengers[$index]['id'] ?? null;
            $payload['seat_number'] = $extras['seats'][$index] ?? null;
            $payload['extra_bags'] = $extras['bags'][$index] ?? 0;
            $booking->passengers()->create($payload);
        }

        $this->rememberPassengers($validated['passengers']);

        return $this->redirectToPayment($booking);
    }

    public function show(string $reference): View
    {
        $booking = Booking::query()
            ->with(['flight.airline', 'flight.originAirport', 'flight.destinationAirport', 'passengers'])
            ->where('booking_reference', $reference)
            ->firstOrFail();

        return view('bookings.show', compact('booking'));
    }

    public function lookup(): View
    {
        return view('bookings.lookup');
    }

    public function find(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'booking_reference' => ['required', 'string', 'max:20'],
            'contact_email' => ['required', 'email'],
        ]);

        $booking = Booking::query()
            ->where('booking_reference', strtoupper(trim($validated['booking_reference'])))
            ->where('contact_email', $validated['contact_email'])
            ->first();

        if (! $booking) {
            return back()
                ->withInput()
                ->withErrors(['booking_reference' => 'No booking found with those details.']);
        }

        return redirect()->route('bookings.show', $booking->booking_reference);
    }

    protected function resolveReturnFlight(Request $request, Flight $outbound): ?Flight
    {
        $id = (int) $request->input('return_flight');

        if ($id < 1) {
            return null;
        }

        $return = Flight::query()
            ->with(['airline', 'originAirport', 'destinationAirport'])
            ->find($id);

        if (! $return) {
            return null;
        }

        if ($return->origin_airport_id !== $outbound->destination_airport_id
            || $return->destination_airport_id !== $outbound->origin_airport_id) {
            return null;
        }

        if ($return->status !== 'scheduled' || $return->departure_at->isPast()) {
            return null;
        }

        if ($return->departure_at->lt($outbound->departure_at)) {
            return null;
        }

        return $return;
    }

    /**
     * @param  array<string, mixed>  $presented
     * @param  array<string, mixed>  $extras
     * @return array<string, mixed>
     */
    protected function itineraryFromPresented(array $presented, array $extras): array
    {
        $legs = array_map(function (array $leg) {
            foreach (['departure_at', 'arrival_at'] as $key) {
                if (($leg[$key] ?? null) instanceof \Carbon\CarbonInterface) {
                    $leg[$key] = $leg[$key]->toIso8601String();
                }
            }

            return $leg;
        }, $presented['legs'] ?? []);

        return [
            'airline' => $presented['airline'] ?? null,
            'airline_code' => $presented['airline_code'] ?? null,
            'flight_number' => $presented['flight_number'] ?? null,
            'origin_code' => $presented['origin_code'] ?? null,
            'origin_city' => $presented['origin_city'] ?? null,
            'origin_name' => $presented['origin_name'] ?? null,
            'destination_code' => $presented['destination_code'] ?? null,
            'destination_city' => $presented['destination_city'] ?? null,
            'destination_name' => $presented['destination_name'] ?? null,
            'departure_at' => $presented['departure_at'] instanceof \Carbon\CarbonInterface
                ? $presented['departure_at']->toIso8601String()
                : ($presented['departure_at'] ?? null),
            'arrival_at' => $presented['arrival_at'] instanceof \Carbon\CarbonInterface
                ? $presented['arrival_at']->toIso8601String()
                : ($presented['arrival_at'] ?? null),
            'cabin_class' => $presented['cabin_class'] ?? null,
            'duration' => $presented['formatted_duration'] ?? null,
            'stops' => $presented['stops'] ?? 0,
            'is_round_trip' => count($legs) > 1,
            'legs' => $legs,
            'extras' => $extras,
        ];
    }

    protected function validateBooking(Request $request, bool $duffel, array $expectedTypes = [], mixed $travelDate = null): array
    {
        $request->merge([
            'passengers' => DateOfBirth::normalizePassengerList($request->input('passengers', [])),
        ]);

        $passengerRules = [
            'passengers' => ['required', 'array', 'min:1', 'max:9'],
            'passengers.*.title' => [$duffel ? 'required' : 'nullable', 'in:mr,mrs,ms,miss,dr'],
            'passengers.*.type' => ['nullable', 'in:adult,child,infant_without_seat,infant'],
            'passengers.*.first_name' => ['required', 'string', 'max:80'],
            'passengers.*.last_name' => ['required', 'string', 'max:80'],
            'passengers.*.dob_day' => [$duffel ? 'required' : 'nullable', 'digits_between:1,2'],
            'passengers.*.dob_month' => [$duffel ? 'required' : 'nullable', 'digits_between:1,2'],
            'passengers.*.dob_year' => [$duffel ? 'required' : 'nullable', 'digits:4'],
            'passengers.*.date_of_birth' => [$duffel ? 'required' : 'nullable', new DateOfBirth],
            'passengers.*.gender' => [$duffel ? 'required' : 'nullable', 'in:male,female'],
            'passengers.*.passport_number' => ['nullable', 'string', 'max:40'],
            'passengers.*.nationality' => ['nullable', 'string', 'max:80'],
            'passengers.*.save' => ['nullable', 'in:0,1'],
        ];

        $validated = $request->validate(array_merge([
            'contact_name' => ['required', 'string', 'max:120'],
            'contact_email' => ['required', 'email', 'max:120'],
            'contact_phone' => [$duffel ? 'required' : 'nullable', 'string', 'max:30'],
            'adults' => ['nullable', 'integer', 'min:1', 'max:9'],
            'children' => ['nullable', 'integer', 'min:0', 'max:8'],
            'infants' => ['nullable', 'integer', 'min:0', 'max:9'],
            'extras.bags' => ['nullable', 'array'],
            'extras.bags.*' => ['nullable', 'integer', 'min:0', 'max:2'],
            'extras.seats' => ['nullable', 'array'],
            'extras.seats.*' => ['nullable', 'string', 'max:8'],
        ], $passengerRules), [
            'passengers.*.date_of_birth.required' => 'Date of birth is required in DD/MM/YYYY format.',
            'passengers.*.dob_day.required' => 'Select the day of birth.',
            'passengers.*.dob_month.required' => 'Select the month of birth.',
            'passengers.*.dob_year.required' => 'Select the year of birth.',
        ]);

        if ($expectedTypes !== [] && count($validated['passengers']) !== count($expectedTypes)) {
            throw ValidationException::withMessages([
                'passengers' => 'Passenger count must match the selected fare.',
            ]);
        }

        foreach ($validated['passengers'] as $index => $passenger) {
            $type = $expectedTypes[$index] ?? PassengerMix::normaliseType($passenger['type'] ?? 'adult');
            $validated['passengers'][$index]['type'] = $type;

            $dob = $passenger['date_of_birth'] ?? null;
            if (blank($dob)) {
                continue;
            }

            if (! PassengerMix::dobMatchesType($type, $dob, $travelDate)) {
                throw ValidationException::withMessages([
                    "passengers.$index.date_of_birth" => PassengerMix::dobError($type),
                ]);
            }
        }

        return $validated;
    }

    protected function slotsFromOffer(array $offerPassengers): array
    {
        if ($offerPassengers === []) {
            return PassengerMix::fromArray(['adults' => 1])->slots();
        }

        return array_map(function (array $passenger) {
            $type = PassengerMix::normaliseType($passenger['type'] ?? 'adult');

            return [
                'type' => $type,
                'label' => PassengerMix::label($type),
                'hint' => PassengerMix::ageHint($type),
            ];
        }, $offerPassengers);
    }

    protected function savedPassengersForCheckout(): array
    {
        if (! auth()->check()) {
            return [];
        }

        return auth()->user()->checkoutPassengers();
    }

    protected function rememberPassengers(array $passengers): void
    {
        if (auth()->check()) {
            auth()->user()->rememberPassengers($passengers);
        }
    }

    protected function redirectToPayment(Booking $booking): RedirectResponse
    {
        if (! $this->paypal->configured()) {
            if ($booking->source === 'duffel') {
                return redirect()
                    ->route('bookings.show', $booking->booking_reference)
                    ->with('error', 'PayPal is not configured. Add PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET, then try booking again.');
            }

            $booking->update([
                'status' => 'confirmed',
                'payment_status' => 'unpaid',
                'booked_at' => now(),
            ]);

            $this->checkout->sendConfirmation($booking->fresh(['passengers', 'flight.airline', 'flight.originAirport', 'flight.destinationAirport']));

            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->with('success', 'Booking held. Add PayPal keys to collect payment automatically.');
        }

        try {
            $url = $this->checkout->startPayPal($booking);
        } catch (PayPalException $exception) {
            return redirect()
                ->route('bookings.show', $booking->booking_reference)
                ->with('error', $exception->getMessage());
        }

        return redirect()->away($url);
    }
}
