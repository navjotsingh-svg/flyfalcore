<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\Airports\AirportSuggestService;
use App\Services\Duffel\DuffelClient;
use App\Services\Flights\FlightSearchService;
use App\Support\PassengerMix;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FlightController extends Controller
{
    public function __construct(
        protected FlightSearchService $search,
        protected DuffelClient $duffel,
        protected AirportSuggestService $airports,
    ) {}

    public function index(Request $request): View
    {
        $mix = PassengerMix::fromRequest($request);
        $filters = array_merge(
            $request->only(['from', 'to', 'date', 'return', 'cabin', 'sort']),
            $mix->query(),
        );
        $result = $this->search->search($filters);

        return view('flights.index', [
            'offers' => $result['offers'],
            'source' => $result['source'],
            'searchMessage' => $result['message'],
            'duffelReady' => $this->duffel->configured(),
            'passengers' => $mix->totalCount(),
            'mix' => $mix,
            'filters' => $filters,
            'fromCode' => $this->search->airportCode($filters['from'] ?? null) ?? '',
            'toCode' => $this->search->airportCode($filters['to'] ?? null) ?? '',
            'fromLabel' => $this->airports->labelFor($filters['from'] ?? null),
            'toLabel' => $this->airports->labelFor($filters['to'] ?? null),
        ]);
    }

    public function show(Flight $flight): View
    {
        $flight->load(['airline', 'originAirport', 'destinationAirport']);

        return view('flights.show', compact('flight'));
    }
}
