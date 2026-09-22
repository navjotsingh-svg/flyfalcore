<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AirportController extends Controller
{
    public function index(Request $request): View
    {
        $airports = Airport::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->orderBy('city')
            ->paginate(20)
            ->withQueryString();

        return view('admin.airports.index', compact('airports'));
    }

    public function create(): View
    {
        return view('admin.airports.form', ['airport' => new Airport]);
    }

    public function store(Request $request): RedirectResponse
    {
        Airport::create($this->validated($request));

        return redirect()->route('admin.airports.index')->with('success', 'Airport created.');
    }

    public function edit(Airport $airport): View
    {
        return view('admin.airports.form', compact('airport'));
    }

    public function update(Request $request, Airport $airport): RedirectResponse
    {
        $airport->update($this->validated($request, $airport->id));

        return redirect()->route('admin.airports.index')->with('success', 'Airport updated.');
    }

    public function destroy(Airport $airport): RedirectResponse
    {
        $airport->delete();

        return redirect()->route('admin.airports.index')->with('success', 'Airport deleted.');
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'code' => ['required', 'string', 'size:3', 'unique:airports,code,'.($id ?? 'NULL')],
            'city' => ['required', 'string', 'max:120'],
            'country' => ['required', 'string', 'max:80'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
