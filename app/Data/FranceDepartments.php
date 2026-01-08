<?php

namespace App\Data;

use Illuminate\Support\Facades\Storage;

/**
 * France departments and regions data
 * Reads data from JSON file in storage
 */
class FranceDepartments
{
    /**
     * Cached departments data
     *
     * @var array|null
     */
    private static ?array $cachedData = null;

    /**
     * Load data from JSON file
     *
     * @return array
     */
    private static function loadData(): array
    {
        if (self::$cachedData === null) {
            $jsonPath = storage_path('app/france-departments.json');
            $jsonContent = file_get_contents($jsonPath);
            self::$cachedData = json_decode($jsonContent, true);
        }
        return self::$cachedData;
    }

    /**
     * Get all departments with their postal code prefixes and region
     *
     * @return array
     */
    public static function getDepartments(): array
    {
        $data = self::loadData();
        return $data['departments'] ?? [];
    }

    /**
     * Get department and region by postal code
     *
     * @param string $postalCode
     * @return array|null
     */
    public static function getByPostalCode(string $postalCode): ?array
    {
        $departments = self::getDepartments();
        
        // Try 3-digit code first (for overseas)
        if (strlen($postalCode) >= 3) {
            $threeDigit = substr($postalCode, 0, 3);
            if (isset($departments[$threeDigit])) {
                return $departments[$threeDigit];
            }
        }
        
        // Then try 2-digit code
        if (strlen($postalCode) >= 2) {
            $twoDigit = substr($postalCode, 0, 2);
            if (isset($departments[$twoDigit])) {
                return $departments[$twoDigit];
            }
        }
        
        return null;
    }

    /**
     * Get all unique regions
     *
     * @return array
     */
    public static function getRegions(): array
    {
        $data = self::loadData();
        return $data['regions'] ?? [];
    }

    /**
     * Get all unique departments names
     *
     * @return array
     */
    public static function getAllDepartments(): array
    {
        $departments = self::getDepartments();
        $names = array_unique(array_map(fn($dept) => $dept['name'], $departments));
        return array_values($names);
    }

    /**
     * Clear cached data (useful for testing)
     *
     * @return void
     */
    public static function clearCache(): void
    {
        self::$cachedData = null;
    }
}
