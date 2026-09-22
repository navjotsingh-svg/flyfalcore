@php
    $dobValue = $value ?? null;
    $parsed = $dobValue ? \Carbon\Carbon::make($dobValue) : null;
    $dayOldKey = $dayOld ?? $dayName;
    $monthOldKey = $monthOld ?? $monthName;
    $yearOldKey = $yearOld ?? $yearName;
    $hiddenOldKey = $hiddenOld ?? ($hiddenName ?? 'date_of_birth');
    $selectedDay = (string) old($dayOldKey, $parsed?->format('d') ?? '');
    $selectedMonth = (string) old($monthOldKey, $parsed?->format('m') ?? '');
    $selectedYear = (string) old($yearOldKey, $parsed?->format('Y') ?? '');
    $required = $required ?? false;
    $alpineIndex = $alpineIndex ?? null;
    $months = [
        '01' => 'January', '02' => 'February', '03' => 'March', '04' => 'April',
        '05' => 'May', '06' => 'June', '07' => 'July', '08' => 'August',
        '09' => 'September', '10' => 'October', '11' => 'November', '12' => 'December',
    ];
    $selectClass = 'w-full rounded-xl border border-slate-200 px-3 py-2.5 bg-slate-50 text-sm';
@endphp
<div>
    <label class="block text-sm font-medium mb-1">Date of birth <span class="text-slate-400 font-normal">(DD/MM/YYYY)</span></label>
    <div class="grid grid-cols-3 gap-2">
        <select name="{{ $dayName }}" {{ $required ? 'required' : '' }} class="{{ $selectClass }}"
                @if(!is_null($alpineIndex)) x-model="passengers[{{ $alpineIndex }}].dob_day" @change="syncDob({{ $alpineIndex }})" @endif>
            <option value="">Day</option>
            @for($day = 1; $day <= 31; $day++)
                @php $dayValue = str_pad((string) $day, 2, '0', STR_PAD_LEFT); @endphp
                <option value="{{ $dayValue }}" @selected($selectedDay === $dayValue || $selectedDay === (string) $day)>{{ $dayValue }}</option>
            @endfor
        </select>
        <select name="{{ $monthName }}" {{ $required ? 'required' : '' }} class="{{ $selectClass }}"
                @if(!is_null($alpineIndex)) x-model="passengers[{{ $alpineIndex }}].dob_month" @change="syncDob({{ $alpineIndex }})" @endif>
            <option value="">Month</option>
            @foreach($months as $monthValue => $monthLabel)
                <option value="{{ $monthValue }}" @selected($selectedMonth === $monthValue || $selectedMonth === (string) (int) $monthValue)>{{ $monthLabel }}</option>
            @endforeach
        </select>
        <select name="{{ $yearName }}" {{ $required ? 'required' : '' }} class="{{ $selectClass }}"
                @if(!is_null($alpineIndex)) x-model="passengers[{{ $alpineIndex }}].dob_year" @change="syncDob({{ $alpineIndex }})" @endif>
            <option value="">Year</option>
            @php
                $range = \App\Support\PassengerMix::yearRange($passengerType ?? null, $travelDate ?? null);
                $minYear = $range['min'];
                $maxYear = $range['max'];
                if ($selectedYear !== '' && ctype_digit($selectedYear)) {
                    $minYear = min($minYear, (int) $selectedYear);
                    $maxYear = max($maxYear, (int) $selectedYear);
                }
            @endphp
            @for($year = $maxYear; $year >= $minYear; $year--)
                <option value="{{ $year }}" @selected($selectedYear === (string) $year)>{{ $year }}</option>
            @endfor
        </select>
    </div>
    <input type="hidden" name="{{ $hiddenName ?? 'date_of_birth' }}" value="{{ old($hiddenOldKey, $parsed?->format('Y-m-d')) }}"
           @if(!is_null($alpineIndex)) x-model="passengers[{{ $alpineIndex }}].date_of_birth" @endif>
    <p class="text-xs text-slate-400 mt-1">{{ $hint ?? 'Must match the passport, as DD/MM/YYYY, and be before today.' }}</p>
</div>
