<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Service to validate team age requirements.
 * 
 * Rules:
 * - Three values: A <= B <= C
 * - All participants must be at least A years old
 * - Teams with a participant under B must have at least one participant aged C or older
 * - OR all participants are at least B years old
 * 
 * Example: A=12, B=16, C=18
 * - Everyone must be at least 12
 * - If someone is under 16, someone else must be 18+
 */
class AgeValidationService
{
    /**
     * Minimum age for any participant (A)
     */
    protected int $minAge;

    /**
     * Intermediate age threshold (B)
     */
    protected int $intermediateAge;

    /**
     * Adult/supervisor age threshold (C)
     */
    protected int $adultAge;

    /**
     * Create a new AgeValidationService instance.
     *
     * @param int|null $minAge Minimum age (A), defaults to config value
     * @param int|null $intermediateAge Intermediate age (B), defaults to config value
     * @param int|null $adultAge Adult age (C), defaults to config value
     * @throws InvalidArgumentException If age values don't satisfy A <= B <= C
     */
    public function __construct(?int $minAge = null, ?int $intermediateAge = null, ?int $adultAge = null)
    {
        $this->minAge = $minAge ?? config('team.age_min', 12);
        $this->intermediateAge = $intermediateAge ?? config('team.age_intermediate', 16);
        $this->adultAge = $adultAge ?? config('team.age_adult', 18);

        $this->validateAgeThresholds();
    }

    /**
     * Validate that age thresholds satisfy A <= B <= C.
     *
     * @throws InvalidArgumentException If thresholds are invalid
     */
    protected function validateAgeThresholds(): void
    {
        if ($this->minAge > $this->intermediateAge || $this->intermediateAge > $this->adultAge) {
            throw new InvalidArgumentException(
                "Age thresholds must satisfy A <= B <= C. " .
                "Got A={$this->minAge}, B={$this->intermediateAge}, C={$this->adultAge}"
            );
        }
    }

    /**
     * Calculate age from birthdate.
     *
     * @param string|Carbon $birthdate The birthdate
     * @param string|Carbon|null $referenceDate Date to calculate age from (defaults to today)
     * @return int The calculated age in years
     */
    public function calculateAge(string|Carbon $birthdate, string|Carbon|null $referenceDate = null): int
    {
        $birthdate = $birthdate instanceof Carbon ? $birthdate : Carbon::parse($birthdate);
        $referenceDate = $referenceDate instanceof Carbon 
            ? $referenceDate 
            : ($referenceDate ? Carbon::parse($referenceDate) : Carbon::now());

        return $birthdate->diffInYears($referenceDate);
    }

    /**
     * Validate a single participant's age.
     *
     * @param int $age The participant's age
     * @return bool True if participant meets minimum age requirement
     */
    public function isParticipantValid(int $age): bool
    {
        return $age >= $this->minAge;
    }

    /**
     * Check if participant is considered a minor (under intermediate age B).
     *
     * @param int $age The participant's age
     * @return bool True if participant is under intermediate age
     */
    public function isMinor(int $age): bool
    {
        return $age < $this->intermediateAge;
    }

    /**
     * Check if participant is considered an adult/supervisor (age C or older).
     *
     * @param int $age The participant's age
     * @return bool True if participant is adult age or older
     */
    public function isAdult(int $age): bool
    {
        return $age >= $this->adultAge;
    }

    /**
     * Validate a team's age composition.
     *
     * Rules:
     * - All participants must be at least A years old
     * - If any participant is under B, at least one must be C or older
     * - OR all participants are at least B years old
     *
     * @param array $ages Array of participant ages (integers)
     * @return array Validation result with 'valid' boolean and 'errors' array
     */
    public function validateTeam(array $ages): array
    {
        $errors = [];
        
        if (empty($ages)) {
            return [
                'valid' => false,
                'errors' => ['Team must have at least one participant.'],
                'details' => null
            ];
        }

        // Check minimum age for all participants
        $underMinAge = [];
        foreach ($ages as $index => $age) {
            if ($age < $this->minAge) {
                $underMinAge[] = [
                    'index' => $index,
                    'age' => $age,
                    'required' => $this->minAge
                ];
            }
        }

        if (!empty($underMinAge)) {
            $errors[] = "All participants must be at least {$this->minAge} years old.";
            return [
                'valid' => false,
                'errors' => $errors,
                'details' => [
                    'participants_under_minimum' => $underMinAge
                ]
            ];
        }

        // Check if anyone is under intermediate age (B)
        $hasMinor = false;
        $hasAdult = false;
        $minors = [];
        $adults = [];

        foreach ($ages as $index => $age) {
            if ($this->isMinor($age)) {
                $hasMinor = true;
                $minors[] = ['index' => $index, 'age' => $age];
            }
            if ($this->isAdult($age)) {
                $hasAdult = true;
                $adults[] = ['index' => $index, 'age' => $age];
            }
        }

        // Rule: If there's a minor, there must be an adult
        if ($hasMinor && !$hasAdult) {
            $errors[] = "Teams with participants under {$this->intermediateAge} must have at least one participant aged {$this->adultAge} or older.";
            return [
                'valid' => false,
                'errors' => $errors,
                'details' => [
                    'minors' => $minors,
                    'adults' => $adults,
                    'needs_adult' => true
                ]
            ];
        }

        return [
            'valid' => true,
            'errors' => [],
            'details' => [
                'minors' => $minors,
                'adults' => $adults,
                'all_intermediate_or_above' => !$hasMinor
            ]
        ];
    }

    /**
     * Validate a team using birthdates instead of ages.
     *
     * @param array $birthdates Array of birthdates (strings or Carbon instances)
     * @param string|Carbon|null $referenceDate Date to calculate ages from
     * @return array Validation result
     */
    public function validateTeamByBirthdates(array $birthdates, string|Carbon|null $referenceDate = null): array
    {
        $ages = array_map(
            fn($birthdate) => $this->calculateAge($birthdate, $referenceDate),
            $birthdates
        );

        return $this->validateTeam($ages);
    }

    /**
     * Get the current age thresholds.
     *
     * @return array Array with 'min', 'intermediate', and 'adult' keys
     */
    public function getThresholds(): array
    {
        return [
            'min' => $this->minAge,
            'intermediate' => $this->intermediateAge,
            'adult' => $this->adultAge
        ];
    }

    /**
     * Get a human-readable explanation of the age rules.
     *
     * @return string The rules explanation
     */
    public function getRulesExplanation(): string
    {
        return "Age requirements: " .
            "All participants must be at least {$this->minAge} years old. " .
            "Teams with participants under {$this->intermediateAge} must include " .
            "at least one participant aged {$this->adultAge} or older.";
    }
}
