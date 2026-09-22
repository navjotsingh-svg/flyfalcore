<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AirlineController extends Controller
{
    public function index(Request $request): View
    {
        $airlines = Airline::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $search = $request->string('q')->toString();
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.airlines.index', compact('airlines'));
    }

    public function create(): View
    {
        return view('admin.airlines.form', ['airline' => new Airline]);
    }

    public function store(Request $request): RedirectResponse
    {
        Airline::create($this->validated($request));

        return redirect()->route('admin.airlines.index')->with('success', 'Airline created.');
    }

    public function edit(Airline $airline): View
    {
        return view('admin.airlines.form', compact('airline'));
    }

    public function update(Request $request, Airline $airline): RedirectResponse
    {
        $airline->update($this->validated($request, $airline->id));

        return redirect()->route('admin.airlines.index')->with('success', 'Airline updated.');
    }

    public function destroy(Airline $airline): RedirectResponse
    {
        $airline->delete();

        return redirect()->route('admin.airlines.index')->with('success', 'Airline deleted.');
    }

    protected function validated(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:3', 'unique:airlines,code,'.($id ?? 'NULL')],
            'country' => ['nullable', 'string', 'max:80'],
            'logo' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
