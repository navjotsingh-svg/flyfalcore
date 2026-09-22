<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = Airline::all()->keyBy('code');
        $airports = Airport::all()->keyBy('code');

        $routes = [
            ['airline' => '6E', 'number' => '204', 'from' => 'DEL', 'to' => 'BOM', 'duration' => 135, 'price' => 4500, 'cabin' => 'economy'],
            ['airline' => '6E', 'number' => '501', 'from' => 'BOM', 'to' => 'BLR', 'duration' => 105, 'price' => 3800, 'cabin' => 'economy'],
            ['airline' => 'AI', 'number' => '865', 'from' => 'DEL', 'to' => 'BOM', 'duration' => 140, 'price' => 6200, 'cabin' => 'economy'],
            ['airline' => 'AI', 'number' => '112', 'from' => 'DEL', 'to' => 'LHR', 'duration' => 540, 'price' => 42000, 'cabin' => 'economy'],
            ['airline' => 'UK', 'number' => '995', 'from' => 'BOM', 'to' => 'DEL', 'duration' => 130, 'price' => 7100, 'cabin' => 'business'],
            ['airline' => 'SG', 'number' => '123', 'from' => 'BLR', 'to' => 'HYD', 'duration' => 75, 'price' => 2900, 'cabin' => 'economy'],
            ['airline' => 'EK', 'number' => '501', 'from' => 'BOM', 'to' => 'DXB', 'duration' => 210, 'price' => 18500, 'cabin' => 'economy'],
            ['airline' => 'EK', 'number' => '512', 'from' => 'DEL', 'to' => 'DXB', 'duration' => 225, 'price' => 19200, 'cabin' => 'economy'],
            ['airline' => 'QR', 'number' => '557', 'from' => 'BOM', 'to' => 'DOH', 'duration' => 240, 'price' => 21000, 'cabin' => 'economy'],
            ['airline' => 'SQ', 'number' => '401', 'from' => 'BOM', 'to' => 'SIN', 'duration' => 330, 'price' => 24500, 'cabin' => 'economy'],
            ['airline' => 'BA', 'number' => '142', 'from' => 'DEL', 'to' => 'LHR', 'duration' => 555, 'price' => 48000, 'cabin' => 'business'],
            ['airline' => '6E', 'number' => '678', 'from' => 'MAA', 'to' => 'BOM', 'duration' => 120, 'price' => 4100, 'cabin' => 'economy'],
            ['airline' => 'AI', 'number' => '770', 'from' => 'HYD', 'to' => 'DEL', 'duration' => 150, 'price' => 5500, 'cabin' => 'economy'],
            ['airline' => 'UK', 'number' => '812', 'from' => 'BLR', 'to' => 'BKK', 'duration' => 255, 'price' => 16800, 'cabin' => 'economy'],
            ['airline' => 'SQ', 'number' => '22', 'from' => 'SIN', 'to' => 'JFK', 'duration' => 1080, 'price' => 65000, 'cabin' => 'economy'],
            ['airline' => 'AI', 'number' => '143', 'from' => 'DEL', 'to' => 'CDG', 'duration' => 510, 'price' => 39000, 'cabin' => 'economy'],
            ['airline' => 'BA', 'number' => '308', 'from' => 'LHR', 'to' => 'CDG', 'duration' => 80, 'price' => 9800, 'cabin' => 'economy'],
            ['airline' => 'AI', 'number' => '139', 'from' => 'DEL', 'to' => 'FCO', 'duration' => 480, 'price' => 36000, 'cabin' => 'economy'],
            ['airline' => 'EK', 'number' => '203', 'from' => 'DXB', 'to' => 'ATH', 'duration' => 265, 'price' => 17500, 'cabin' => 'economy'],
        ];

        $departureTimes = ['06:30', '08:15', '10:00', '12:45', '15:30', '18:00', '21:15'];

        for ($day = 1; $day <= 30; $day++) {
            foreach ($routes as $index => $route) {
                if ($day % 2 === 0 && $index % 3 === 0) {
                    continue;
                }

                $airline = $airlines[$route['airline']];
                $origin = $airports[$route['from']];
                $destination = $airports[$route['to']];
                $time = $departureTimes[$index % count($departureTimes)];
                $departureAt = Carbon::today()->addDays($day)->setTimeFromTimeString($time);
                $arrivalAt = (clone $departureAt)->addMinutes($route['duration']);
                $seats = $route['cabin'] === 'business' ? 48 : 180;
                $available = random_int((int) ($seats * 0.2), $seats);

                Flight::updateOrCreate(
                    [
                        'airline_id' => $airline->id,
                        'flight_number' => $route['number'],
                        'departure_at' => $departureAt,
                    ],
                    [
                        'origin_airport_id' => $origin->id,
                        'destination_airport_id' => $destination->id,
                        'arrival_at' => $arrivalAt,
                        'duration_minutes' => $route['duration'],
                        'price' => $route['price'] + random_int(-500, 1500),
                        'cabin_class' => $route['cabin'],
                        'total_seats' => $seats,
                        'available_seats' => $available,
                        'status' => 'scheduled',
                    ]
                );
            }
        }
    }
}
