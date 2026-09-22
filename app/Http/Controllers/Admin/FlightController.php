<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Flight;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function index(Request $request): View
    {
        $flights = Flight::query()
            ->with(['airline', 'originAirport', 'destinationAirport'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('flight_number', 'like', "%{$search}%")
                        ->orWhere('cabin_class', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhereHas('airline', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%"))
                        ->orWhereHas('originAirport', fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"))
                        ->orWhereHas('destinationAirport', fn ($q) => $q->where('code', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%"));
                });
            })
            ->latest('departure_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.flights.index', compact('flights'));
    }

    public function create(): View
    {
        return view('admin.flights.form', [
            'flight' => new Flight(['status' => 'scheduled', 'cabin_class' => 'economy', 'total_seats' => 180, 'available_seats' => 180]),
            ...$this->formLists(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Flight::create($this->validated($request));

        return redirect()->route('admin.flights.index')->with('success', 'Flight created.');
    }

    public function edit(Flight $flight): View
    {
        return view('admin.flights.form', [
            'flight' => $flight,
            ...$this->formLists(),
        ]);
    }

    public function update(Request $request, Flight $flight): RedirectResponse
    {
        $flight->update($this->validated($request));

        return redirect()->route('admin.flights.index')->with('success', 'Flight updated.');
    }

    public function destroy(Flight $flight): RedirectResponse
    {
        $flight->delete();

        return redirect()->route('admin.flights.index')->with('success', 'Flight deleted.');
    }

    protected function formLists(): array
    {
        return [
            'airlines' => Airline::query()->orderBy('name')->get(),
            'airports' => Airport::query()->orderBy('city')->get(),
        ];
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'airline_id' => ['required', 'exists:airlines,id'],
            'flight_number' => ['required', 'string', 'max:10'],
            'origin_airport_id' => ['required', 'exists:airports,id', 'different:destination_airport_id'],
            'destination_airport_id' => ['required', 'exists:airports,id'],
            'departure_at' => ['required', 'date'],
            'arrival_at' => ['required', 'date', 'after:departure_at'],
            'duration_minutes' => ['nullable', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0'],
            'cabin_class' => ['required', 'in:economy,premium_economy,business,first'],
            'total_seats' => ['required', 'integer', 'min:1'],
            'available_seats' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:scheduled,delayed,cancelled,completed'],
        ]);

        if (empty($data['duration_minutes'])) {
            $data['duration_minutes'] = Carbon::parse($data['departure_at'])->diffInMinutes(Carbon::parse($data['arrival_at']));
        }

        return $data;
    }
}
