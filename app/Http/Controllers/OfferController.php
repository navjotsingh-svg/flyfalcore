<?php

namespace App\Http\Controllers;

use App\Exceptions\DuffelException;
use App\Services\Duffel\DuffelClient;
use App\Services\Flights\FlightSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(
        protected DuffelClient $duffel,
        protected FlightSearchService $search,
    ) {}

    public function show(string $offer): View|RedirectResponse
    {
        try {
            $offerData = $this->search->presentDuffelOffer($this->duffel->getOffer($offer));
        } catch (DuffelException $exception) {
            return redirect()
                ->route('flights.index')
                ->with('error', $exception->getMessage());
        }

        return view('flights.offer', ['offer' => $offerData]);
    }
}
