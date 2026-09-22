<?php

namespace App\Http\Controllers;

use App\Models\Airport;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $airports = Airport::query()
            ->where('is_active', true)
            ->orderBy('city')
            ->get();

        $airportsByCode = $airports->keyBy('code');

        $deals = collect([
            ['city' => 'Santorini', 'code' => 'ATH', 'image' => 'https://images.unsplash.com/photo-1613395877344-13d4a8e0d49e?auto=format&fit=crop&w=800&q=80'],
            ['city' => 'Dubai', 'code' => 'DXB', 'image' => 'https://images.unsplash.com/photo-1512453979798-5ea138f9dba6?auto=format&fit=crop&w=800&q=80'],
            ['city' => 'Paris', 'code' => 'CDG', 'image' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=800&q=80'],
            ['city' => 'Maldives', 'code' => 'SIN', 'image' => 'https://images.unsplash.com/photo-1514282401047-d79a71a590e8?auto=format&fit=crop&w=800&q=80'],
            ['city' => 'New York', 'code' => 'JFK', 'image' => 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?auto=format&fit=crop&w=800&q=80'],
        ])->map(function (array $deal) use ($airportsByCode) {
            $deal['airport'] = $airportsByCode->get($deal['code']);

            return $deal;
        });

        $destinations = collect([
            [
                'city' => 'London',
                'country' => 'United Kingdom',
                'code' => 'LHR',
                'image' => 'https://images.unsplash.com/photo-1513635269971-016998ad2c9c?auto=format&fit=crop&w=1400&q=80',
                'large' => true,
            ],
            [
                'city' => 'Paris',
                'country' => 'France',
                'code' => 'CDG',
                'image' => 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=900&q=80',
                'large' => false,
            ],
            [
                'city' => 'Dubai',
                'country' => 'United Arab Emirates',
                'code' => 'DXB',
                'image' => 'https://images.unsplash.com/photo-1512453979798-5ea138f9dba6?auto=format&fit=crop&w=900&q=80',
                'large' => false,
            ],
            [
                'city' => 'Rome',
                'country' => 'Italy',
                'code' => 'FCO',
                'image' => 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?auto=format&fit=crop&w=900&q=80',
                'large' => false,
            ],
            [
                'city' => 'New York',
                'country' => 'USA',
                'code' => 'JFK',
                'image' => 'https://images.unsplash.com/photo-1485871981521-5b1fd3805eee?auto=format&fit=crop&w=900&q=80',
                'large' => false,
            ],
        ])->map(function (array $destination) use ($airportsByCode) {
            $destination['airport'] = $airportsByCode->get($destination['code']);

            return $destination;
        });

        $articles = [
            [
                'title' => 'A quiet week in Bali, from rice terraces to the sea',
                'excerpt' => 'Where to stay, when to fly, and the routes that keep the journey easy.',
                'image' => 'https://images.unsplash.com/photo-1537996194471-e657df975ab2?auto=format&fit=crop&w=1200&q=80',
                'tag' => 'Guides',
            ],
            [
                'title' => 'High alpine escapes you can book this season',
                'excerpt' => 'Mountain towns, scenic approaches, and flexible return fares.',
                'image' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1200&q=80',
                'tag' => 'Inspiration',
            ],
            [
                'title' => 'How to pack light for a two-week city hop',
                'excerpt' => 'Carry-on only, cabin-friendly, and ready for last-minute changes.',
                'image' => 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=1200&q=80',
                'tag' => 'Tips',
            ],
        ];

        return view('home.index', compact(
            'airports',
            'deals',
            'destinations',
            'articles'
        ));
    }
}
