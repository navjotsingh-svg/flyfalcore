<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
            $table->string('payment_status', 32)->default('unpaid')->change();
            $table->string('source', 20)->default('local')->after('booking_reference');
            $table->string('currency', 3)->default('INR')->after('total_amount');
            $table->string('duffel_offer_id')->nullable()->after('flight_id');
            $table->string('duffel_offer_request_id')->nullable()->after('duffel_offer_id');
            $table->string('duffel_order_id')->nullable()->after('duffel_offer_request_id');
            $table->string('duffel_booking_reference')->nullable()->after('duffel_order_id');
            $table->string('paypal_order_id')->nullable()->after('duffel_booking_reference');
            $table->string('paypal_capture_id')->nullable()->after('paypal_order_id');
            $table->json('itinerary')->nullable()->after('paypal_capture_id');
            $table->json('payment_payload')->nullable()->after('itinerary');
            $table->text('fulfillment_error')->nullable()->after('payment_payload');
        });

        Schema::table('passengers', function (Blueprint $table) {
            $table->string('title', 10)->nullable()->after('booking_id');
            $table->string('duffel_passenger_id')->nullable()->after('seat_number');
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('bookings', function (Blueprint $table) {
                $table->dropForeign(['flight_id']);
            });
        }

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('flight_id')->nullable()->change();
        });

        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('bookings', function (Blueprint $table) {
                $table->foreign('flight_id')->references('id')->on('flights')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('passengers', function (Blueprint $table) {
            $table->dropColumn(['title', 'duffel_passenger_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'source',
                'currency',
                'duffel_offer_id',
                'duffel_offer_request_id',
                'duffel_order_id',
                'duffel_booking_reference',
                'paypal_order_id',
                'paypal_capture_id',
                'itinerary',
                'payment_payload',
                'fulfillment_error',
            ]);
        });
    }
};
