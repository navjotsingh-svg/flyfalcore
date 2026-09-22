<?php

namespace Tests\Feature;

use App\Mail\OneTimePasscodeMail;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Flight;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_sent_to_traveler_login(): void
    {
        $this->get('/account/bookings')->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_signup_sends_otp_then_creates_the_account_after_verify(): void
    {
        Mail::fake();

        $this->post('/signup', [
            'name' => 'Ada Lovelace',
            'email' => 'ada@example.com',
            'password' => 'password12',
            'password_confirmation' => 'password12',
        ])->assertRedirect(route('signup'));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'ada@example.com']);
        $this->assertDatabaseCount('otps', 1);

        $code = '';
        Mail::assertSent(OneTimePasscodeMail::class, function (OneTimePasscodeMail $mail) use (&$code) {
            $code = $mail->code;

            return $mail->hasTo('ada@example.com');
        });

        $this->get('/signup')
            ->assertOk()
            ->assertSee('Enter the 6-digit code')
            ->assertSee('ada@example.com');

        $this->post('/otp/verify', [
            'purpose' => Otp::SIGNUP,
            'code' => $code,
        ])->assertRedirect(route('account.bookings'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ada@example.com',
            'name' => 'Ada Lovelace',
        ]);
        $this->assertNotNull(User::query()->where('email', 'ada@example.com')->value('email_verified_at'));

        $this->get('/account/bookings')
            ->assertOk()
            ->assertSee('My trips')
            ->assertSee('No trips yet');
    }

    public function test_login_claims_guest_bookings_for_the_same_email(): void
    {
        $user = User::factory()->create([
            'email' => 'ada@example.com',
            'password' => 'password12',
        ]);

        $booking = $this->makeBooking('ada@example.com');

        $this->post('/login', [
            'email' => 'ada@example.com',
            'password' => 'password12',
        ])->assertRedirect(route('account.bookings'));

        $this->assertSame($user->id, $booking->fresh()->user_id);

        $this->get('/account/bookings')
            ->assertOk()
            ->assertSee($booking->booking_reference)
            ->assertSee('London');
    }

    public function test_signed_in_booking_is_saved_to_the_account(): void
    {
        $user = User::factory()->create();
        $flight = $this->makeFlight();

        $this->actingAs($user)->post(route('bookings.store', $flight), [
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'contact_phone' => '+447700900123',
            'passengers' => [[
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'date_of_birth' => '1988-01-15',
                'gender' => 'female',
            ]],
        ]);

        $this->assertDatabaseHas('bookings', [
            'user_id' => $user->id,
            'contact_email' => $user->email,
        ]);

        $this->assertTrue(
            $user->savedPassengers()->where('first_name', 'Ada')->whereDate('date_of_birth', '1988-01-15')->exists()
        );
    }

    public function test_checkout_shows_saved_passengers_and_dd_mm_yyyy_dob(): void
    {
        $user = User::factory()->create();
        $flight = $this->makeFlight();

        $user->savedPassengers()->create([
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'date_of_birth' => '1988-01-15',
            'gender' => 'female',
        ]);

        $this->actingAs($user)
            ->get(route('bookings.create', $flight))
            ->assertOk()
            ->assertSee('DD/MM/YYYY')
            ->assertSee('1900')
            ->assertDontSee('>'.now()->year.'<', false)
            ->assertSee('Saved passengers')
            ->assertSee('Ada Lovelace')
            ->assertSee('Choose from passengers you have added before')
            ->assertSee('Extra checked bag')
            ->assertSee('Choose a seat')
            ->assertDontSee('Duffel');
    }

    public function test_european_dob_format_is_accepted_and_invalid_dates_are_rejected(): void
    {
        $user = User::factory()->create();
        $flight = $this->makeFlight();

        $this->actingAs($user)->post(route('bookings.store', $flight), [
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'passengers' => [[
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'date_of_birth' => '15/01/1988',
                'gender' => 'female',
            ]],
        ]);

        $this->assertTrue(
            \App\Models\Passenger::query()->where('first_name', 'Ada')->whereDate('date_of_birth', '1988-01-15')->exists()
        );

        $this->actingAs($user)->from(route('bookings.create', $flight))->post(route('bookings.store', $flight), [
            'contact_name' => $user->name,
            'contact_email' => $user->email,
            'passengers' => [[
                'first_name' => 'Alan',
                'last_name' => 'Turing',
                'dob_day' => '31',
                'dob_month' => '02',
                'dob_year' => '1990',
                'gender' => 'male',
            ]],
        ])->assertRedirect(route('bookings.create', $flight))
            ->assertSessionHasErrors('passengers.0.date_of_birth');
    }

    public function test_account_saved_passengers_can_be_managed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('account.passengers.store'), [
            'title' => 'ms',
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'dob_day' => '15',
            'dob_month' => '01',
            'dob_year' => '1988',
            'gender' => 'female',
        ])->assertRedirect(route('account.passengers'));

        $this->assertTrue(
            $user->savedPassengers()->where('first_name', 'Ada')->whereDate('date_of_birth', '1988-01-15')->exists()
        );

        $this->actingAs($user)
            ->get(route('account.passengers'))
            ->assertOk()
            ->assertSee('Ada Lovelace')
            ->assertSee('15/01/1988');
    }

    protected function makeBooking(string $email): Booking
    {
        $flight = $this->makeFlight();

        return Booking::create([
            'source' => 'local',
            'flight_id' => $flight->id,
            'contact_name' => 'Ada Lovelace',
            'contact_email' => $email,
            'passengers_count' => 1,
            'total_amount' => 199,
            'currency' => 'INR',
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'itinerary' => [
                'airline' => 'IndiGo',
                'origin_city' => 'London',
                'destination_city' => 'New York',
            ],
        ]);
    }

    protected function makeFlight(): Flight
    {
        $airline = Airline::create(['name' => 'IndiGo', 'code' => '6E', 'country' => 'India']);
        $origin = Airport::create(['name' => 'Heathrow', 'code' => 'LHR', 'city' => 'London', 'country' => 'United Kingdom']);
        $destination = Airport::create(['name' => 'JFK', 'code' => 'JFK', 'city' => 'New York', 'country' => 'USA']);

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
