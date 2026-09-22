<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SavedPassenger;
use App\Support\DateOfBirth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedPassengerController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $user->importSavedPassengersFromBookings();

        $editing = null;

        if ($request->filled('edit')) {
            $editing = $user->savedPassengers()->findOrFail($request->integer('edit'));
        }

        return view('account.passengers', [
            'passengers' => $user->savedPassengers()->orderBy('first_name')->orderBy('last_name')->get(),
            'editing' => $editing ?? new SavedPassenger,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $user = $request->user();
        $dob = $validated['date_of_birth'] ?? null;

        $existing = $user->savedPassengers()
            ->where('first_name', $validated['first_name'])
            ->where('last_name', $validated['last_name'])
            ->when(
                $dob,
                fn ($query) => $query->whereDate('date_of_birth', $dob),
                fn ($query) => $query->whereNull('date_of_birth'),
            )
            ->first();

        if ($existing) {
            $existing->update($validated);
        } else {
            $user->savedPassengers()->create($validated);
        }

        return redirect()->route('account.passengers')->with('success', 'Passenger saved for faster checkout.');
    }

    public function update(Request $request, SavedPassenger $savedPassenger): RedirectResponse
    {
        abort_unless($savedPassenger->user_id === $request->user()->id, 403);

        $savedPassenger->update($this->validated($request));

        return redirect()->route('account.passengers')->with('success', 'Passenger details updated.');
    }

    public function destroy(Request $request, SavedPassenger $savedPassenger): RedirectResponse
    {
        abort_unless($savedPassenger->user_id === $request->user()->id, 403);

        $savedPassenger->delete();

        return back()->with('success', 'Passenger removed from your list.');
    }

    protected function validated(Request $request): array
    {
        $request->merge([
            'date_of_birth' => DateOfBirth::normalize(
                $request->input('date_of_birth'),
                $request->input('dob_day'),
                $request->input('dob_month'),
                $request->input('dob_year'),
            ),
        ]);

        return $request->validate([
            'title' => ['nullable', 'in:mr,mrs,ms,miss,dr'],
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'date_of_birth' => ['nullable', new DateOfBirth],
            'gender' => ['nullable', 'in:male,female'],
            'passport_number' => ['nullable', 'string', 'max:40'],
            'nationality' => ['nullable', 'string', 'max:80'],
        ]);
    }
}
