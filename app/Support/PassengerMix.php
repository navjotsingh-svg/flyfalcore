<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;

class PassengerMix
{
    public const ADULT = 'adult';

    public const CHILD = 'child';

    public const INFANT = 'infant_without_seat';

    public function __construct(
        public readonly int $adults = 1,
        public readonly int $children = 0,
        public readonly int $infants = 0,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return static::fromArray($request->all());
    }

    public static function fromArray(array $input): self
    {
        $hasBreakdown = array_key_exists('adults', $input)
            || array_key_exists('children', $input)
            || array_key_exists('infants', $input);

        $legacy = $input['passengers'] ?? 1;
        if (is_array($legacy)) {
            $legacy = max(1, count($legacy));
        }

        $adults = $hasBreakdown
            ? (int) ($input['adults'] ?? 1)
            : (int) $legacy;
        $children = $hasBreakdown ? (int) ($input['children'] ?? 0) : 0;
        $infants = $hasBreakdown ? (int) ($input['infants'] ?? 0) : 0;

        $adults = max(1, min(9, $adults));
        $children = max(0, min(8, $children));
        $infants = max(0, min(9, $infants));

        if ($adults + $children > 9) {
            $children = 9 - $adults;
        }

        if ($infants > $adults) {
            $infants = $adults;
        }

        if ($adults + $children + $infants > 9) {
            $infants = max(0, 9 - $adults - $children);
        }

        return new self($adults, $children, $infants);
    }

    public static function fromTypes(array $types): self
    {
        $normalised = array_map([static::class, 'normaliseType'], $types);

        return new self(
            count(array_filter($normalised, fn ($type) => $type === self::ADULT)),
            count(array_filter($normalised, fn ($type) => $type === self::CHILD)),
            count(array_filter($normalised, fn ($type) => $type === self::INFANT)),
        );
    }

    public static function normaliseType(?string $type): string
    {
        return match ($type) {
            'child' => self::CHILD,
            'infant', 'infant_without_seat', 'infant_with_seat' => self::INFANT,
            default => self::ADULT,
        };
    }

    public static function label(string $type): string
    {
        return match (static::normaliseType($type)) {
            self::CHILD => 'Child',
            self::INFANT => 'Infant',
            default => 'Adult',
        };
    }

    public static function ageHint(string $type): string
    {
        return match (static::normaliseType($type)) {
            self::CHILD => '2–11 years on travel date',
            self::INFANT => 'Under 2 years on travel date',
            default => '12+ years on travel date',
        };
    }

    public static function dobError(string $type): string
    {
        return match (static::normaliseType($type)) {
            self::CHILD => 'This child fare needs a date of birth of 2–11 years on the travel date.',
            self::INFANT => 'This infant fare needs a date of birth under 2 years on the travel date.',
            default => 'This adult fare needs a date of birth of 12 years or older on the travel date.',
        };
    }

    public static function typeForAge(int $age): string
    {
        if ($age < 2) {
            return self::INFANT;
        }

        if ($age < 12) {
            return self::CHILD;
        }

        return self::ADULT;
    }

    public static function ageInYears(?string $dob, CarbonInterface|string|null $on = null): ?int
    {
        if (blank($dob)) {
            return null;
        }

        try {
            $birth = Carbon::parse($dob)->startOfDay();
            $onDate = Carbon::parse($on ?? now())->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        return (int) $birth->diffInYears($onDate);
    }

    public static function dobMatchesType(string $type, ?string $dob, CarbonInterface|string|null $on = null): bool
    {
        $age = static::ageInYears($dob, $on);

        if ($age === null) {
            return false;
        }

        return static::typeForAge($age) === static::normaliseType($type);
    }

    /**
     * @return array{min: int, max: int}
     */
    public static function yearRange(?string $type = null, CarbonInterface|string|null $travelDate = null, CarbonInterface|string|null $today = null): array
    {
        $today = Carbon::parse($today ?? now())->startOfDay();
        $travel = Carbon::parse($travelDate ?? $today)->startOfDay();
        $latestYear = $today->copy()->subDay()->year;

        if ($type === null || $type === '') {
            return ['min' => 1900, 'max' => $latestYear];
        }

        return match (static::normaliseType($type)) {
            self::INFANT => [
                'min' => $travel->copy()->subYears(2)->addDay()->year,
                'max' => min($latestYear, $travel->year),
            ],
            self::CHILD => [
                'min' => $travel->copy()->subYears(12)->addDay()->year,
                'max' => min($latestYear, $travel->copy()->subYears(2)->year),
            ],
            default => [
                'min' => 1900,
                'max' => min($latestYear, $travel->copy()->subYears(12)->year),
            ],
        };
    }

    public function types(): array
    {
        return array_merge(
            array_fill(0, $this->adults, self::ADULT),
            array_fill(0, $this->children, self::CHILD),
            array_fill(0, $this->infants, self::INFANT),
        );
    }

    public function slots(): array
    {
        return array_map(fn (string $type) => [
            'type' => $type,
            'label' => static::label($type),
            'hint' => static::ageHint($type),
        ], $this->types());
    }

    public function duffelPassengers(): array
    {
        return array_map(fn (string $type) => ['type' => $type], $this->types());
    }

    public function seatedCount(): int
    {
        return $this->adults + $this->children;
    }

    public function totalCount(): int
    {
        return $this->adults + $this->children + $this->infants;
    }

    public function summary(): string
    {
        $parts = [$this->adults.' Adult'.($this->adults === 1 ? '' : 's')];

        if ($this->children > 0) {
            $parts[] = $this->children.' Child'.($this->children === 1 ? '' : 'ren');
        }

        if ($this->infants > 0) {
            $parts[] = $this->infants.' Infant'.($this->infants === 1 ? '' : 's');
        }

        return implode(', ', $parts);
    }

    public function query(): array
    {
        return [
            'adults' => $this->adults,
            'children' => $this->children,
            'infants' => $this->infants,
            'passengers' => $this->totalCount(),
        ];
    }
}
