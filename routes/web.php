<?php

use App\Http\Controllers\Admin\AirlineController as AdminAirlineController;
use App\Http\Controllers\Admin\AirportController as AdminAirportController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\ContactMessageController as AdminContactMessageController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FlightController as AdminFlightController;
use App\Http\Controllers\Admin\NewsletterSubscriberController as AdminNewsletterSubscriberController;
use App\Http\Controllers\Admin\PassengerController as AdminPassengerController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Account\BookingController as AccountBookingController;
use App\Http\Controllers\Account\ProfileController as AccountProfileController;
use App\Http\Controllers\Account\SavedPassengerController as AccountSavedPassengerController;
use App\Http\Controllers\AirportSuggestController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\FlightController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OfferController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PayPalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/hotels', [PageController::class, 'hotels'])->name('hotels');
Route::get('/cars', [PageController::class, 'cars'])->name('cars');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/corporate-travel', [PageController::class, 'corporate'])->name('corporate');
Route::get('/partnerships', [PageController::class, 'partnerships'])->name('partnerships');
Route::get('/travelers', [PageController::class, 'travelers'])->name('travelers');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::post('/contact', [PageController::class, 'storeContact'])->name('contact.store');
Route::get('/destinations', [PageController::class, 'destinations'])->name('destinations');
Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
Route::get('/signup', [PageController::class, 'signup'])->name('signup');
Route::post('/signup', [PageController::class, 'storeSignup'])->name('signup.store');
Route::post('/otp/verify', [OtpController::class, 'verify'])->middleware('throttle:10,1')->name('otp.verify');
Route::post('/otp/resend', [OtpController::class, 'resend'])->middleware('throttle:5,1')->name('otp.resend');
Route::post('/otp/cancel', [OtpController::class, 'cancel'])->name('otp.cancel');

Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', fn () => redirect()->route('account.bookings'))->name('index');
    Route::get('/bookings', [AccountBookingController::class, 'index'])->name('bookings');
    Route::get('/passengers', [AccountSavedPassengerController::class, 'index'])->name('passengers');
    Route::post('/passengers', [AccountSavedPassengerController::class, 'store'])->name('passengers.store');
    Route::put('/passengers/{savedPassenger}', [AccountSavedPassengerController::class, 'update'])->name('passengers.update');
    Route::delete('/passengers/{savedPassenger}', [AccountSavedPassengerController::class, 'destroy'])->name('passengers.destroy');
    Route::get('/profile', [AccountProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [AccountProfileController::class, 'update'])->name('profile.update');
});
Route::post('/newsletter', [PageController::class, 'storeNewsletter'])->name('newsletter.store');

Route::get('/airports/suggest', AirportSuggestController::class)->name('airports.suggest');
Route::get('/flights', [FlightController::class, 'index'])->name('flights.index');
Route::get('/flights/{flight}', [FlightController::class, 'show'])->whereNumber('flight')->name('flights.show');
Route::get('/flights/{flight}/book', [BookingController::class, 'create'])->whereNumber('flight')->name('bookings.create');
Route::post('/flights/{flight}/book', [BookingController::class, 'store'])->whereNumber('flight')->name('bookings.store');

Route::get('/offers/{offer}', [OfferController::class, 'show'])->name('offers.show');
Route::get('/offers/{offer}/book', [BookingController::class, 'createFromOffer'])->name('offers.book');
Route::post('/offers/{offer}/book', [BookingController::class, 'storeFromOffer'])->name('offers.store');

Route::get('/bookings/lookup', [BookingController::class, 'lookup'])->name('bookings.lookup');
Route::post('/bookings/lookup', [BookingController::class, 'find'])->name('bookings.find');
Route::get('/bookings/{reference}', [BookingController::class, 'show'])->name('bookings.show');

Route::get('/payments/paypal/{reference}/success', [PayPalController::class, 'success'])->name('paypal.success');
Route::get('/payments/paypal/{reference}/cancel', [PayPalController::class, 'cancel'])->name('paypal.cancel');
Route::get('/payments/paypal/{reference}/retry', [PayPalController::class, 'retry'])->name('paypal.retry');
Route::post('/bookings/{reference}/issue-ticket', [PayPalController::class, 'issueTicket'])->name('bookings.issue');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'create'])->name('login');
    Route::post('login', [AdminAuthController::class, 'store'])->name('login.store');

    Route::middleware(['auth', 'admin'])->group(function () {
        Route::post('logout', [AdminAuthController::class, 'destroy'])->name('logout');
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::resource('airlines', AdminAirlineController::class)->except(['show']);
        Route::resource('airports', AdminAirportController::class)->except(['show']);
        Route::resource('flights', AdminFlightController::class)->except(['show']);
        Route::resource('bookings', AdminBookingController::class);
        Route::resource('passengers', AdminPassengerController::class)->except(['show']);
        Route::resource('users', AdminUserController::class)->except(['show']);
        Route::get('messages', [AdminContactMessageController::class, 'index'])->name('messages.index');
        Route::delete('messages/{message}', [AdminContactMessageController::class, 'destroy'])->name('messages.destroy');
        Route::get('subscribers', [AdminNewsletterSubscriberController::class, 'index'])->name('subscribers.index');
        Route::delete('subscribers/{subscriber}', [AdminNewsletterSubscriberController::class, 'destroy'])->name('subscribers.destroy');
    });
});

