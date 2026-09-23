<?php

namespace Tests\Feature;

use App\Mail\BookingConfirmed;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class FlightCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_duffel_offers_are_listed_when_the_api_responds(): void
    {
        config()->set('services.duffel.token', 'duffel_test_fake');

        Http::fake([
            'https://api.duffel.com/air/offer_requests*' => Http::response([
                'data' => ['id' => 'orq_test'],
            ], 201),
            'https://api.duffel.com/air/offers*' => Http::response([
                'data' => [$this->offerFixture()],
            ], 200),
        ]);

        $this->get('/flights?from=LHR&to=JFK&date='.now()->addWeek()->toDateString())
            ->assertOk()
            ->assertSee('British Airways')
            ->assertSee('Total fare')
            ->assertDontSee('Duffel')
            ->assertSee('Book Now')
            ->assertSee('Modify search');
    }

    public function test_return_search_sends_two_slices_and_shows_both_legs(): void
    {
        config()->set('services.duffel.token', 'duffel_test_fake');

        $out = now()->addWeek()->toDateString();
        $back = now()->addWeeks(2)->toDateString();

        Http::fake([
            'https://api.duffel.com/air/offer_requests*' => Http::response([
                'data' => ['id' => 'orq_return'],
            ], 201),
            'https://api.duffel.com/air/offers*' => Http::response([
                'data' => [$this->offerFixture(roundTrip: true)],
            ], 200),
        ]);

        $this->get('/flights?from=LHR&to=JFK&date='.$out.'&return_date='.$back)
            ->assertOk()
            ->assertSee('Round trip')
            ->assertSee('Outbound')
            ->assertSee('Return')
            ->assertSee('JFK')
            ->assertSee('LHR');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($out, $back) {
            if (! str_contains($request->url(), '/air/offer_requests')) {
                return false;
            }

            $slices = data_get($request->data(), 'data.slices') ?? data_get($request->data(), 'slices');

            return is_array($slices)
                && count($slices) === 2
                && ($slices[0]['origin'] ?? null) === 'LHR'
                && ($slices[0]['destination'] ?? null) === 'JFK'
                && ($slices[0]['departure_date'] ?? null) === $out
                && ($slices[1]['origin'] ?? null) === 'JFK'
                && ($slices[1]['destination'] ?? null) === 'LHR'
                && ($slices[1]['departure_date'] ?? null) === $back;
        });
    }

    public function test_local_return_pairs_outbound_and_inbound_flights(): void
    {
        $outbound = $this->makeFlight();
        $inbound = Flight::create([
            'airline_id' => $outbound->airline_id,
            'flight_number' => '205',
            'origin_airport_id' => $outbound->destination_airport_id,
            'destination_airport_id' => $outbound->origin_airport_id,
            'departure_at' => now()->addDays(12)->setTime(11, 0),
            'arrival_at' => now()->addDays(12)->setTime(19, 0),
            'duration_minutes' => 480,
            'price' => 149.00,
            'cabin_class' => 'economy',
            'total_seats' => 180,
            'available_seats' => 40,
            'status' => 'scheduled',
        ]);

        $this->get('/flights?from=LHR&to=JFK&date='.$outbound->departure_at->toDateString().'&return_date='.$inbound->departure_at->toDateString())
            ->assertOk()
            ->assertSee('Round trip')
            ->assertSee('Outbound')
            ->assertSee('Return')
            ->assertSee('return_flight='.$inbound->id, false);

        $this->get(route('bookings.create', ['flight' => $outbound, 'return_flight' => $inbound->id]))
            ->assertOk()
            ->assertSee('Outbound')
            ->assertSee('Return');

        $this->post(route('bookings.store', $outbound), [
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'return_flight' => $inbound->id,
            'adults' => 1,
            'passengers' => [[
                'type' => 'adult',
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'date_of_birth' => '1988-01-15',
                'gender' => 'female',
            ]],
        ]);

        $this->assertDatabaseHas('bookings', [
            'contact_email' => 'ada@example.com',
            'total_amount' => 348.00,
        ]);

        $booking = \App\Models\Booking::query()->where('contact_email', 'ada@example.com')->first();
        $this->assertNotNull($booking);
        $this->assertCount(2, $booking->legs());
        $this->assertSame('Return', $booking->legs()[1]['label']);
        $this->assertSame(49, $outbound->fresh()->available_seats);
        $this->assertSame(39, $inbound->fresh()->available_seats);
    }

    public function test_search_sends_adult_child_and_infant_types_to_duffel(): void
    {
        config()->set('services.duffel.token', 'duffel_test_fake');

        Http::fake([
            'https://api.duffel.com/air/offer_requests*' => Http::response([
                'data' => ['id' => 'orq_test'],
            ], 201),
            'https://api.duffel.com/air/offers*' => Http::response([
                'data' => [$this->offerFixture()],
            ], 200),
        ]);

        $this->get('/flights?from=LHR&to=JFK&date='.now()->addWeek()->toDateString().'&adults=1&children=1&infants=1')
            ->assertOk();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if (! str_contains($request->url(), '/air/offer_requests')) {
                return false;
            }

            $types = collect(data_get($request->data(), 'data.passengers') ?? data_get($request->data(), 'passengers'))
                ->pluck('type')
                ->all();

            return $types === ['adult', 'child', 'infant_without_seat'];
        });
    }

    public function test_infant_date_of_birth_is_rejected_on_an_adult_fare(): void
    {
        $flight = $this->makeFlight();

        $this->from(route('bookings.create', $flight))->post(route('bookings.store', $flight), [
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
            'passengers' => [[
                'type' => 'adult',
                'first_name' => 'Baby',
                'last_name' => 'Lovelace',
                'date_of_birth' => '2026-03-15',
                'gender' => 'female',
            ]],
        ])->assertRedirect(route('bookings.create', $flight))
            ->assertSessionHasErrors('passengers.0.date_of_birth');
    }

    public function test_infant_and_adult_can_be_booked_together_locally(): void
    {
        $flight = $this->makeFlight();

        $this->get(route('bookings.create', ['flight' => $flight, 'adults' => 1, 'infants' => 1]))
            ->assertOk()
            ->assertSee('Passenger 1 · Adult')
            ->assertSee('Passenger 2 · Infant')
            ->assertSee('Under 2 years')
            ->assertSee('Extra checked bag')
            ->assertSee('Choose a seat')
            ->assertSee('Economy')
            ->assertDontSee('Duffel');

        $this->post(route('bookings.store', $flight), [
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'adults' => 1,
            'children' => 0,
            'infants' => 1,
            'passengers' => [
                [
                    'type' => 'adult',
                    'first_name' => 'Ada',
                    'last_name' => 'Lovelace',
                    'date_of_birth' => '1988-01-15',
                    'gender' => 'female',
                ],
                [
                    'type' => 'infant_without_seat',
                    'first_name' => 'Baby',
                    'last_name' => 'Lovelace',
                    'date_of_birth' => '2026-03-15',
                    'gender' => 'female',
                ],
            ],
        ]);

        $this->assertDatabaseHas('bookings', [
            'contact_email' => 'ada@example.com',
            'passengers_count' => 2,
        ]);
        $this->assertDatabaseHas('passengers', [
            'first_name' => 'Baby',
            'type' => 'infant_without_seat',
        ]);
    }

    public function test_seat_and_extra_bag_are_added_to_the_booking_total(): void
    {
        Mail::fake();

        $flight = $this->makeFlight();

        $this->post(route('bookings.store', $flight), [
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'adults' => 1,
            'children' => 0,
            'infants' => 0,
            'passengers' => [[
                'type' => 'adult',
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'date_of_birth' => '1988-01-15',
                'gender' => 'female',
            ]],
            'extras' => [
                'bags' => [1],
                'seats' => ['12A'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'contact_email' => 'ada@example.com',
            'total_amount' => 1999.00,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('passengers', [
            'first_name' => 'Ada',
            'seat_number' => '12A',
            'extra_bags' => 1,
        ]);

        Mail::assertSent(BookingConfirmed::class, function (BookingConfirmed $mail) {
            $html = $mail->render();

            return $mail->hasTo('ada@example.com')
                && str_contains($html, 'Booking confirmed')
                && str_contains($html, 'Seat 12A')
                && str_contains($html, 'Economy')
                && ! str_contains($html, 'Duffel');
        });
    }

    public function test_duffel_connection_failure_falls_back_to_local_flights(): void
    {
        config()->set('services.duffel.token', 'duffel_test_fake');

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException("cURL error 6: Couldn't resolve host 'api.duffel.com'");
        });

        $this->makeFlight();

        $this->get('/flights?from=LHR&to=JFK&date='.now()->addDays(5)->toDateString())
            ->assertOk()
            ->assertSee('Live airline search is unavailable')
            ->assertSee('IndiGo')
            ->assertDontSee("Couldn't resolve host");
    }

    public function test_local_booking_redirects_to_paypal(): void
    {
        config()->set('services.paypal.client_id', 'paypal-client');
        config()->set('services.paypal.client_secret', 'paypal-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-token',
                'expires_in' => 30000,
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'links' => [[
                    'rel' => 'approve',
                    'href' => 'https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1',
                ]],
            ], 201),
        ]);

        $flight = $this->makeFlight();

        $this->post(route('bookings.store', $flight), [
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'contact_phone' => '+447700900123',
            'passengers' => [[
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'date_of_birth' => '1988-01-15',
                'gender' => 'female',
            ]],
        ])->assertRedirect('https://www.sandbox.paypal.com/checkoutnow?token=PAYPAL-ORDER-1');

        $this->assertDatabaseHas('bookings', [
            'contact_email' => 'ada@example.com',
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'payment_status' => 'pending',
        ]);
    }

    public function test_paypal_return_captures_payment_for_a_local_booking(): void
    {
        Mail::fake();
        config()->set('services.paypal.client_id', 'paypal-client');
        config()->set('services.paypal.client_secret', 'paypal-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-token',
                'expires_in' => 30000,
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [
                        'captures' => [['id' => 'CAP-1', 'status' => 'COMPLETED']],
                    ],
                ]],
            ], 201),
        ]);

        $booking = \App\Models\Booking::create([
            'source' => 'local',
            'flight_id' => $this->makeFlight()->id,
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'passengers_count' => 1,
            'total_amount' => 199,
            'currency' => 'USD',
            'status' => 'pending',
            'payment_status' => 'pending',
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'itinerary' => ['airline' => 'IndiGo', 'origin_city' => 'London', 'destination_city' => 'New York'],
        ]);

        $this->get(route('paypal.success', ['reference' => $booking->booking_reference, 'token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect(route('bookings.show', $booking->booking_reference));

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'paypal_capture_id' => 'CAP-1',
        ]);

        Mail::assertSent(BookingConfirmed::class, function (BookingConfirmed $mail) use ($booking) {
            return $mail->hasTo('ada@example.com')
                && str_contains($mail->render(), $booking->booking_reference)
                && ! str_contains($mail->render(), 'Duffel');
        });
    }

    public function test_duffel_order_falls_back_when_balance_payment_type_is_rejected(): void
    {
        Mail::fake();
        config()->set('services.duffel.token', 'duffel_test_fake');
        config()->set('services.paypal.client_id', 'paypal-client');
        config()->set('services.paypal.client_secret', 'paypal-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, 'paypal.com/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token', 'expires_in' => 30000]);
            }

            if (str_contains($url, 'paypal.com/v2/checkout/orders/PAYPAL-ORDER-1/capture')) {
                return Http::response([
                    'id' => 'PAYPAL-ORDER-1',
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'payments' => [
                            'captures' => [['id' => 'CAP-1', 'status' => 'COMPLETED']],
                        ],
                    ]],
                ], 201);
            }

            if (str_contains($url, '/air/offers/off_test_1')) {
                return Http::response(['data' => $this->offerFixture()]);
            }

            if (str_contains($url, '/air/orders')) {
                $body = $request->data();
                $paymentType = data_get($body, 'data.payments.0.type') ?? data_get($body, 'payments.0.type');

                if ($paymentType === 'arc_bsp_cash') {
                    return Http::response([
                        'data' => ['id' => 'ord_1', 'booking_reference' => 'ABC123'],
                    ], 201);
                }

                return Http::response([
                    'errors' => [[
                        'code' => 'validation_error',
                        'title' => 'Invalid field',
                        'message' => 'The selected payment type is not available for this offer',
                        'documentation_url' => 'https://duffel.com/docs/api/overview/response-handling',
                        'source' => ['field' => 'type', 'pointer' => '/payments/0/type'],
                        'type' => 'validation_error',
                    ]],
                ], 422);
            }

            return Http::response(['errors' => [['message' => 'unexpected '.$url]]], 404);
        });

        $booking = \App\Models\Booking::create([
            'source' => 'duffel',
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'contact_phone' => '+447700900123',
            'passengers_count' => 1,
            'total_amount' => 350,
            'currency' => 'GBP',
            'status' => 'pending',
            'payment_status' => 'pending',
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'duffel_offer_id' => 'off_test_1',
            'itinerary' => ['airline' => 'British Airways', 'origin_city' => 'London', 'destination_city' => 'New York'],
        ]);

        $booking->passengers()->create([
            'title' => 'ms',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'date_of_birth' => '1988-01-15',
            'gender' => 'female',
            'duffel_passenger_id' => 'pas_1',
        ]);

        $this->get(route('paypal.success', ['reference' => $booking->booking_reference, 'token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect(route('bookings.show', $booking->booking_reference))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'payment_status' => 'paid',
            'status' => 'confirmed',
            'duffel_order_id' => 'ord_1',
        ]);

        Mail::assertSent(BookingConfirmed::class);
        $this->get(route('bookings.show', $booking->booking_reference))
            ->assertOk()
            ->assertDontSee('Duffel');
    }

    public function test_duffel_order_validation_error_is_shown_instead_of_raw_http_exception(): void
    {
        config()->set('services.duffel.token', 'duffel_test_fake');
        config()->set('services.paypal.client_id', 'paypal-client');
        config()->set('services.paypal.client_secret', 'paypal-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        Http::fake([
            'https://api-m.sandbox.paypal.com/v1/oauth2/token' => Http::response([
                'access_token' => 'paypal-token',
                'expires_in' => 30000,
            ]),
            'https://api-m.sandbox.paypal.com/v2/checkout/orders/PAYPAL-ORDER-1/capture' => Http::response([
                'id' => 'PAYPAL-ORDER-1',
                'status' => 'COMPLETED',
                'purchase_units' => [[
                    'payments' => [
                        'captures' => [['id' => 'CAP-1', 'status' => 'COMPLETED']],
                    ],
                ]],
            ], 201),
            'https://api.duffel.com/air/offers/*' => Http::response(['data' => $this->offerFixture()]),
            'https://api.duffel.com/air/orders' => Http::response([
                'errors' => [[
                    'code' => 'validation_required',
                    'title' => 'Required field',
                    'message' => 'Passenger date of birth does not match the booked fare type',
                    'documentation_url' => 'https://duffel.com/docs/api/overview/response-handling',
                    'source' => ['field' => 'born_on', 'pointer' => '/passengers/0/born_on'],
                    'type' => 'validation_error',
                ]],
            ], 422),
        ]);

        $booking = \App\Models\Booking::create([
            'source' => 'duffel',
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'contact_phone' => '+447700900123',
            'passengers_count' => 1,
            'total_amount' => 350,
            'currency' => 'GBP',
            'status' => 'pending',
            'payment_status' => 'pending',
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'duffel_offer_id' => 'off_test_1',
            'itinerary' => ['airline' => 'British Airways', 'origin_city' => 'London', 'destination_city' => 'New York'],
        ]);

        $booking->passengers()->create([
            'title' => 'ms',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'date_of_birth' => '1988-01-15',
            'gender' => 'female',
            'duffel_passenger_id' => 'pas_1',
        ]);

        $this->get(route('paypal.success', ['reference' => $booking->booking_reference, 'token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect(route('bookings.show', $booking->booking_reference))
            ->assertSessionHas('error')
            ->assertSessionMissing('success');

        $this->get(route('bookings.show', $booking->booking_reference))
            ->assertOk()
            ->assertSee('Passenger date of birth does not match the booked fare type')
            ->assertDontSee('HTTP request returned status code 422')
            ->assertDontSee('Duffel');
    }

    public function test_airline_order_includes_selected_seat_and_bag_services(): void
    {
        Mail::fake();
        config()->set('services.duffel.token', 'duffel_test_fake');
        config()->set('services.paypal.client_id', 'paypal-client');
        config()->set('services.paypal.client_secret', 'paypal-secret');
        config()->set('services.paypal.base_url', 'https://api-m.sandbox.paypal.com');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, 'paypal.com/v1/oauth2/token')) {
                return Http::response(['access_token' => 'paypal-token', 'expires_in' => 30000]);
            }

            if (str_contains($url, 'paypal.com/v2/checkout/orders/PAYPAL-ORDER-1/capture')) {
                return Http::response([
                    'id' => 'PAYPAL-ORDER-1',
                    'status' => 'COMPLETED',
                    'purchase_units' => [[
                        'payments' => [
                            'captures' => [['id' => 'CAP-1', 'status' => 'COMPLETED']],
                        ],
                    ]],
                ], 201);
            }

            if (str_contains($url, '/air/offers/off_test_1')) {
                return Http::response(['data' => $this->offerFixture()]);
            }

            if (str_contains($url, '/air/orders')) {
                $services = data_get($request->data(), 'data.services') ?? data_get($request->data(), 'services');

                if (! is_array($services) || collect($services)->pluck('id')->all() !== ['ase_bag_1', 'ase_seat_1']) {
                    return Http::response(['errors' => [['message' => 'missing services']]], 422);
                }

                return Http::response([
                    'data' => ['id' => 'ord_2', 'booking_reference' => 'XYZ999'],
                ], 201);
            }

            return Http::response(['errors' => [['message' => 'unexpected '.$url]]], 404);
        });

        $booking = \App\Models\Booking::create([
            'source' => 'duffel',
            'contact_name' => 'Ada Lovelace',
            'contact_email' => 'ada@example.com',
            'contact_phone' => '+447700900123',
            'passengers_count' => 1,
            'total_amount' => 390,
            'currency' => 'GBP',
            'status' => 'pending',
            'payment_status' => 'pending',
            'paypal_order_id' => 'PAYPAL-ORDER-1',
            'duffel_offer_id' => 'off_test_1',
            'itinerary' => [
                'airline' => 'British Airways',
                'origin_city' => 'London',
                'destination_city' => 'New York',
                'cabin_class' => 'economy',
                'extras' => [
                    'total' => 40,
                    'airline_total' => 40,
                    'services' => [
                        ['id' => 'ase_bag_1', 'quantity' => 1],
                        ['id' => 'ase_seat_1', 'quantity' => 1],
                    ],
                    'lines' => [
                        ['label' => '23 kg checked bag × 1', 'amount' => 25],
                        ['label' => 'Seat 12A', 'amount' => 15],
                    ],
                ],
            ],
        ]);

        $booking->passengers()->create([
            'title' => 'ms',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'date_of_birth' => '1988-01-15',
            'gender' => 'female',
            'seat_number' => '12A',
            'extra_bags' => 1,
            'duffel_passenger_id' => 'pas_1',
        ]);

        $this->get(route('paypal.success', ['reference' => $booking->booking_reference, 'token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect(route('bookings.show', $booking->booking_reference))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'duffel_order_id' => 'ord_2',
            'status' => 'confirmed',
        ]);
    }

    protected function offerFixture(bool $roundTrip = false): array
    {
        $outbound = [
            'duration' => 'PT8H',
            'segments' => [[
                'departing_at' => now()->addWeek()->setTime(9, 0)->toIso8601String(),
                'arriving_at' => now()->addWeek()->setTime(17, 0)->toIso8601String(),
                'marketing_carrier' => ['iata_code' => 'BA', 'name' => 'British Airways'],
                'marketing_carrier_flight_number' => '178',
                'origin' => ['iata_code' => 'LHR', 'name' => 'Heathrow', 'city_name' => 'London'],
                'destination' => ['iata_code' => 'JFK', 'name' => 'John F Kennedy', 'city_name' => 'New York'],
            ]],
        ];

        $slices = [$outbound];

        if ($roundTrip) {
            $slices[] = [
                'duration' => 'PT7H30M',
                'segments' => [[
                    'departing_at' => now()->addWeeks(2)->setTime(18, 0)->toIso8601String(),
                    'arriving_at' => now()->addWeeks(2)->setTime(6, 30)->addDay()->toIso8601String(),
                    'marketing_carrier' => ['iata_code' => 'BA', 'name' => 'British Airways'],
                    'marketing_carrier_flight_number' => '179',
                    'origin' => ['iata_code' => 'JFK', 'name' => 'John F Kennedy', 'city_name' => 'New York'],
                    'destination' => ['iata_code' => 'LHR', 'name' => 'Heathrow', 'city_name' => 'London'],
                ]],
            ];
        }

        return [
            'id' => 'off_test_1',
            'offer_request_id' => 'orq_test',
            'total_amount' => $roundTrip ? '620.00' : '350.00',
            'total_currency' => 'GBP',
            'cabin_class' => 'economy',
            'owner' => ['name' => 'British Airways', 'iata_code' => 'BA'],
            'passengers' => [['id' => 'pas_1', 'type' => 'adult']],
            'slices' => $slices,
        ];
    }

    protected function makeFlight(): Flight
    {
        $airline = Airline::create([
            'name' => 'IndiGo',
            'code' => '6E',
            'country' => 'India',
        ]);

        $origin = Airport::create([
            'name' => 'Heathrow',
            'code' => 'LHR',
            'city' => 'London',
            'country' => 'United Kingdom',
        ]);

        $destination = Airport::create([
            'name' => 'JFK',
            'code' => 'JFK',
            'city' => 'New York',
            'country' => 'USA',
        ]);

        return Flight::create([
            'airline_id' => $airline->id,
            'flight_number' => '204',
            'origin_airport_id' => $origin->id,
            'destination_airport_id' => $destination->id,
            'departure_at' => now()->addDays(5)->setTime(10, 0),
            'arrival_at' => now()->addDays(5)->setTime(18, 0),
            'duration_minutes' => 480,
            'price' => 199.00,
            'cabin_class' => 'economy',
            'total_seats' => 180,
            'available_seats' => 50,
            'status' => 'scheduled',
        ]);
    }
}
