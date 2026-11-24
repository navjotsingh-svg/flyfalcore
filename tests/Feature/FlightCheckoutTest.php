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

    public function test_without_tbo_credentials_local_search_does_not_show_live_usd_fares(): void
    {
        config()->set('services.tbo.client_id', null);
        config()->set('services.tbo.username', null);
        config()->set('services.tbo.password', null);

        $this->get('/flights?from=LHR&to=JFK&date='.now()->addWeek()->toDateString())
            ->assertOk()
            ->assertDontSee('British Airways')
            ->assertDontSee('US$')
            ->assertDontSee('American Airlines');
    }

    public function test_tbo_offers_are_listed_in_indian_rupees(): void
    {
        config()->set('services.tbo.client_id', 'ApiIntegrationNew');
        config()->set('services.tbo.username', 'tbo_user');
        config()->set('services.tbo.password', 'tbo_pass');
        config()->set('services.tbo.currency', 'INR');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, 'Authenticate')) {
                return Http::response([
                    'TokenId' => 'tok_test',
                    'Error' => ['ErrorCode' => 0, 'ErrorMessage' => ''],
                ]);
            }

            if (str_contains($url, '/Search')) {
                $adults = data_get($request->data(), 'AdultCount');
                $journey = data_get($request->data(), 'JourneyType');
                $origin = data_get($request->data(), 'Segments.0.Origin');

                if ((int) $adults !== 1 || (int) $journey !== 1 || $origin !== 'DEL') {
                    return Http::response(['Error' => ['ErrorCode' => 1, 'ErrorMessage' => 'bad search']], 400);
                }

                return Http::response($this->tboSearchFixture());
            }

            return Http::response(['errors' => [['message' => 'unexpected '.$url]]], 404);
        });

        $this->get('/flights?from=DEL&to=BOM&date='.now()->addWeek()->toDateString())
            ->assertOk()
            ->assertSee('Air India')
            ->assertSee('₹')
            ->assertSee('8,450')
            ->assertSee('Select')
            ->assertDontSee('Duffel')
            ->assertDontSee('TBO');
    }

    public function test_tbo_return_search_sends_two_segments(): void
    {
        config()->set('services.tbo.client_id', 'ApiIntegrationNew');
        config()->set('services.tbo.username', 'tbo_user');
        config()->set('services.tbo.password', 'tbo_pass');

        $out = now()->addWeek()->toDateString();
        $back = now()->addWeeks(2)->toDateString();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), 'Authenticate')) {
                return Http::response(['TokenId' => 'tok_test', 'Error' => ['ErrorCode' => 0]]);
            }

            if (str_contains($request->url(), '/Search')) {
                return Http::response($this->tboSearchFixture(roundTrip: true));
            }

            return Http::response(['errors' => [['message' => 'unexpected']]], 404);
        });

        $this->get('/flights?from=DEL&to=BOM&date='.$out.'&return_date='.$back)
            ->assertOk()
            ->assertSee('Air India')
            ->assertSee('₹');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($out, $back) {
            if (! str_contains($request->url(), '/Search')) {
                return false;
            }

            $segments = $request->data()['Segments'] ?? [];

            return (int) ($request->data()['JourneyType'] ?? 0) === 2
                && count($segments) === 2
                && ($segments[0]['Origin'] ?? null) === 'DEL'
                && ($segments[0]['Destination'] ?? null) === 'BOM'
                && str_starts_with((string) ($segments[0]['PreferredDepartureTime'] ?? ''), $out)
                && ($segments[1]['Origin'] ?? null) === 'BOM'
                && ($segments[1]['Destination'] ?? null) === 'DEL'
                && str_starts_with((string) ($segments[1]['PreferredDepartureTime'] ?? ''), $back);
        });
    }

    public function test_tbo_fare_quote_locks_inr_price_on_checkout(): void
    {
        config()->set('services.tbo.client_id', 'ApiIntegrationNew');
        config()->set('services.tbo.username', 'tbo_user');
        config()->set('services.tbo.password', 'tbo_pass');
        config()->set('services.tbo.currency', 'INR');

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();

            if (str_contains($url, 'Authenticate')) {
                return Http::response(['TokenId' => 'tok_test', 'Error' => ['ErrorCode' => 0, 'ErrorMessage' => '']]);
            }

            if (str_contains($url, '/Search')) {
                return Http::response($this->tboSearchFixture());
            }

            if (str_contains($url, '/FareQuote')) {
                return Http::response($this->tboQuoteFixture());
            }

            return Http::response(['errors' => [['message' => 'unexpected '.$url]]], 404);
        });

        $listing = $this->get('/flights?from=DEL&to=BOM&date='.now()->addWeek()->toDateString());
        $listing->assertOk();

        preg_match('/offers\/(tbo-[a-f0-9]+)/', $listing->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $this->get(route('offers.show', $matches[1]))
            ->assertOk()
            ->assertSee('Fare options')
            ->assertSee('9,100.00')
            ->assertDontSee('8,450.00')
            ->assertSee('Go to checkout')
            ->assertDontSee('TBO')
            ->assertDontSee('Duffel');
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
            ->assertSee('Depart')
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

    protected function tboSearchFixture(bool $roundTrip = false): array
    {
        $depart = now()->addWeek()->setTime(8, 0)->format('Y-m-d\TH:i:s');
        $arrive = now()->addWeek()->setTime(10, 15)->format('Y-m-d\TH:i:s');

        $outbound = [[
            'Airline' => ['AirlineCode' => 'AI', 'AirlineName' => 'Air India', 'FlightNumber' => '101'],
            'Origin' => [
                'Airport' => ['AirportCode' => 'DEL', 'AirportName' => 'Indira Gandhi', 'CityName' => 'Delhi'],
                'DepTime' => $depart,
            ],
            'Destination' => [
                'Airport' => ['AirportCode' => 'BOM', 'AirportName' => 'Mumbai', 'CityName' => 'Mumbai'],
                'ArrTime' => $arrive,
            ],
            'Duration' => 135,
            'CabinClass' => 2,
            'Baggage' => '15 KG',
            'CabinBaggage' => '7 KG',
        ]];

        $result = [
            'ResultIndex' => 'OB1',
            'IsLCC' => true,
            'IsRefundable' => false,
            'AirlineCode' => 'AI',
            'Fare' => [
                'Currency' => 'INR',
                'PublishedFare' => 8450.00,
                'OfferedFare' => 8000.00,
                'BaseFare' => 7200.00,
            ],
            'Segments' => [$outbound],
        ];

        if ($roundTrip) {
            $result['Segments'][] = [[
                'Airline' => ['AirlineCode' => 'AI', 'AirlineName' => 'Air India', 'FlightNumber' => '102'],
                'Origin' => [
                    'Airport' => ['AirportCode' => 'BOM', 'AirportName' => 'Mumbai', 'CityName' => 'Mumbai'],
                    'DepTime' => now()->addWeeks(2)->setTime(18, 0)->format('Y-m-d\TH:i:s'),
                ],
                'Destination' => [
                    'Airport' => ['AirportCode' => 'DEL', 'AirportName' => 'Indira Gandhi', 'CityName' => 'Delhi'],
                    'ArrTime' => now()->addWeeks(2)->setTime(20, 20)->format('Y-m-d\TH:i:s'),
                ],
                'Duration' => 140,
                'CabinClass' => 2,
                'Baggage' => '15 KG',
                'CabinBaggage' => '7 KG',
            ]];
        }

        return [
            'Response' => [
                'ResponseStatus' => 1,
                'TraceId' => 'trace_test',
                'Error' => ['ErrorCode' => 0, 'ErrorMessage' => ''],
                'Results' => [[$result]],
            ],
        ];
    }

    protected function tboQuoteFixture(): array
    {
        $search = $this->tboSearchFixture();
        $result = $search['Response']['Results'][0][0];
        $result['Fare']['PublishedFare'] = 9100.00;
        $result['Fare']['Currency'] = 'INR';
        $result['IsPriceChanged'] = true;

        return [
            'Response' => [
                'ResponseStatus' => 1,
                'TraceId' => 'trace_test',
                'IsPriceChanged' => true,
                'Error' => ['ErrorCode' => 0, 'ErrorMessage' => ''],
                'Results' => $result,
            ],
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
