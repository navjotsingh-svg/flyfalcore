<?php

namespace App\Http\Controllers;

use App\Exceptions\TboException;
use App\Services\Flights\FlightSearchService;
use App\Support\FareFamily;
use App\Support\PassengerMix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(protected FlightSearchService $search) {}

    public function show(Request $request, string $offer): View|RedirectResponse
    {
        try {
            $offerData = $this->search->liveOffer($offer);
            $family = $this->search->liveFareFamily($offerData);
        } catch (TboException $exception) {
            return redirect()
                ->route('flights.index')
                ->with('error', $exception->getMessage());
        }

        return view('flights.fares', [
            'mode' => $offerData['source'] ?? 'tbo',
            'offer' => $offerData,
            'cards' => FareFamily::cards($family),
            'mix' => PassengerMix::fromRequest($request),
            'filters' => $request->only(['from', 'to', 'date', 'return_date', 'cabin', 'adults', 'children', 'infants']),
        ]);
    }
}
