<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained()->cascadeOnDelete();
            $table->string('flight_number');
            $table->foreignId('origin_airport_id')->constrained('airports')->cascadeOnDelete();
            $table->foreignId('destination_airport_id')->constrained('airports')->cascadeOnDelete();
            $table->dateTime('departure_at');
            $table->dateTime('arrival_at');
            $table->unsignedInteger('duration_minutes');
            $table->decimal('price', 10, 2);
            $table->string('cabin_class')->default('economy');
            $table->unsignedInteger('total_seats')->default(180);
            $table->unsignedInteger('available_seats')->default(180);
            $table->enum('status', ['scheduled', 'delayed', 'cancelled', 'completed'])->default('scheduled');
            $table->timestamps();

            $table->unique(['airline_id', 'flight_number', 'departure_at'], 'flights_number_departure_unique');
            $table->index(['origin_airport_id', 'destination_airport_id', 'departure_at'], 'flights_route_departure_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
