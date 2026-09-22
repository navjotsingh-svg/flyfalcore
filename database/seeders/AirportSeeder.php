<?php

namespace Database\Seeders;

use App\Models\Airport;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    public function run(): void
    {
        $airports = [
            ['name' => 'Indira Gandhi International Airport', 'code' => 'DEL', 'city' => 'New Delhi', 'country' => 'India', 'latitude' => 28.5562, 'longitude' => 77.1000],
            ['name' => 'Chhatrapati Shivaji Maharaj International Airport', 'code' => 'BOM', 'city' => 'Mumbai', 'country' => 'India', 'latitude' => 19.0896, 'longitude' => 72.8656],
            ['name' => 'Kempegowda International Airport', 'code' => 'BLR', 'city' => 'Bengaluru', 'country' => 'India', 'latitude' => 13.1986, 'longitude' => 77.7066],
            ['name' => 'Chennai International Airport', 'code' => 'MAA', 'city' => 'Chennai', 'country' => 'India', 'latitude' => 12.9941, 'longitude' => 80.1709],
            ['name' => 'Netaji Subhas Chandra Bose International Airport', 'code' => 'CCU', 'city' => 'Kolkata', 'country' => 'India', 'latitude' => 22.6547, 'longitude' => 88.4467],
            ['name' => 'Rajiv Gandhi International Airport', 'code' => 'HYD', 'city' => 'Hyderabad', 'country' => 'India', 'latitude' => 17.2403, 'longitude' => 78.4294],
            ['name' => 'Dubai International Airport', 'code' => 'DXB', 'city' => 'Dubai', 'country' => 'UAE', 'latitude' => 25.2532, 'longitude' => 55.3657],
            ['name' => 'Singapore Changi Airport', 'code' => 'SIN', 'city' => 'Singapore', 'country' => 'Singapore', 'latitude' => 1.3644, 'longitude' => 103.9915],
            ['name' => 'Heathrow Airport', 'code' => 'LHR', 'city' => 'London', 'country' => 'United Kingdom', 'latitude' => 51.4700, 'longitude' => -0.4543],
            ['name' => 'Hamad International Airport', 'code' => 'DOH', 'city' => 'Doha', 'country' => 'Qatar', 'latitude' => 25.2731, 'longitude' => 51.6081],
            ['name' => 'Suvarnabhumi Airport', 'code' => 'BKK', 'city' => 'Bangkok', 'country' => 'Thailand', 'latitude' => 13.6900, 'longitude' => 100.7501],
            ['name' => 'John F. Kennedy International Airport', 'code' => 'JFK', 'city' => 'New York', 'country' => 'USA', 'latitude' => 40.6413, 'longitude' => -73.7781],
            ['name' => 'Charles de Gaulle Airport', 'code' => 'CDG', 'city' => 'Paris', 'country' => 'France', 'latitude' => 49.0097, 'longitude' => 2.5479],
            ['name' => 'Leonardo da Vinci International Airport', 'code' => 'FCO', 'city' => 'Rome', 'country' => 'Italy', 'latitude' => 41.8003, 'longitude' => 12.2389],
            ['name' => 'Athens International Airport', 'code' => 'ATH', 'city' => 'Athens', 'country' => 'Greece', 'latitude' => 37.9364, 'longitude' => 23.9445],
        ];

        foreach ($airports as $airport) {
            Airport::updateOrCreate(['code' => $airport['code']], $airport);
        }
    }
}
