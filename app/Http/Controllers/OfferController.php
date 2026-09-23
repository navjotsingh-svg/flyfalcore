<?php

namespace App\Http\Controllers;

use App\Exceptions\DuffelException;
use App\Services\Duffel\DuffelClient;
use App\Services\Flights\FlightSearchService;
use App\Support\FareFamily;
use App\Support\PassengerMix;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(
        protected DuffelClient $duffel,
        protected FlightSearchService $search,
    ) {}

    public function show(Request $request, string $offer): View|RedirectResponse
    {
        try {
            $offerData = $this->search->presentDuffelOffer($this->duffel->getOffer($offer));
        } catch (DuffelException $exception) {
            return redirect()
                ->route('flights.index')
                ->with('error', $exception->getMessage());
        }

        $family = collect([$offerData]);
        $requestId = $offerData['offer_request_id'] ?? null;

        if (filled($requestId)) {
            try {
                $siblings = collect($this->duffel->listOffers((string) $requestId, 50))
                    ->map(fn (array $row) => $this->search->presentDuffelOffer($row, $requestId))
                    ->filter(fn (array $row) => ($row['fingerprint'] ?? '') === ($offerData['fingerprint'] ?? ''))
                    ->values();

                if ($siblings->isNotEmpty()) {
                    $family = $siblings;
                }
            } catch (DuffelException) {
                // Keep the selected offer when sibling fares cannot be loaded.
            }
        }

        return view('flights.fares', [
            'mode' => 'duffel',
            'offer' => $offerData,
            'cards' => FareFamily::cards($family),
            'mix' => PassengerMix::fromRequest($request),
            'filters' => $request->only(['from', 'to', 'date', 'return_date', 'cabin', 'adults', 'children', 'infants']),
        ]);
    }
}
