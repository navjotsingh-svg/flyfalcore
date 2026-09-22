<?php

namespace Database\Seeders;

use App\Models\Airline;
use Illuminate\Database\Seeder;

class AirlineSeeder extends Seeder
{
    public function run(): void
    {
        $airlines = [
            ['name' => 'IndiGo', 'code' => '6E', 'country' => 'India'],
            ['name' => 'Air India', 'code' => 'AI', 'country' => 'India'],
            ['name' => 'Vistara', 'code' => 'UK', 'country' => 'India'],
            ['name' => 'SpiceJet', 'code' => 'SG', 'country' => 'India'],
            ['name' => 'Emirates', 'code' => 'EK', 'country' => 'UAE'],
            ['name' => 'Qatar Airways', 'code' => 'QR', 'country' => 'Qatar'],
            ['name' => 'Singapore Airlines', 'code' => 'SQ', 'country' => 'Singapore'],
            ['name' => 'British Airways', 'code' => 'BA', 'country' => 'United Kingdom'],
        ];

        foreach ($airlines as $airline) {
            Airline::updateOrCreate(['code' => $airline['code']], $airline);
        }
    }
}
