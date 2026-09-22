<?php

namespace App\Http\Controllers;

use App\Services\Airports\AirportSuggestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AirportSuggestController extends Controller
{
    public function __invoke(Request $request, AirportSuggestService $suggest): JsonResponse
    {
        $query = (string) $request->query('q', '');

        return response()->json([
            'data' => $suggest->suggest($query)->all(),
        ]);
    }
}
