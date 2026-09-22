<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Falcore Admin',
            'email' => 'admin@falcore.test',
            'is_admin' => true,
        ]);

        $this->call([
            AirlineSeeder::class,
            AirportSeeder::class,
            FlightSeeder::class,
        ]);
    }
}
