<?php

namespace App\Http\Controllers;

use App\Models\Flight;
use App\Services\Airports\AirportSuggestService;
use App\Services\Duffel\DuffelClient;
use App\Services\Flights\FlightSearchService;
use App\Support\FareFamily;
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
            $request->only(['from', 'to', 'date', 'return', 'return_date', 'cabin', 'sort']),
            $mix->query(),
        );
        $filters['return_date'] = FlightSearchService::returnDate($filters);
        $result = $this->search->search($filters);
        $cabin = filled($filters['cabin'] ?? null) ? (string) $filters['cabin'] : null;
        $offers = FareFamily::collapseForListing($result['offers'], $cabin);

        return view('flights.index', [
            'offers' => $offers,
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
            'trip' => filled($filters['return_date'] ?? null) ? 'return' : 'oneway',
        ]);
    }

    public function show(Flight $flight): View
    {
        $flight->load(['airline', 'originAirport', 'destinationAirport']);

        return view('flights.show', compact('flight'));
    }

    public function fares(Request $request, Flight $flight): View
    {
        $flight->load(['airline', 'originAirport', 'destinationAirport']);
        $mix = PassengerMix::fromRequest($request);
        $returnFlight = null;

        if (filled($request->query('return_flight'))) {
            $returnFlight = Flight::query()
                ->with(['airline', 'originAirport', 'destinationAirport'])
                ->find($request->query('return_flight'));
        }

        $offer = $this->search->presentLocalOffer($flight, $returnFlight);
        $cards = FareFamily::cards([$offer]);

        return view('flights.fares', [
            'mode' => 'local',
            'offer' => $offer,
            'cards' => $cards,
            'mix' => $mix,
            'filters' => array_merge($request->only(['from', 'to', 'date', 'return_date', 'cabin']), $mix->query()),
        ]);
    }
}
