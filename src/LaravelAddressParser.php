<?php

declare(strict_types=1);

namespace Awaisjameel\LaravelAddressParser;

final class LaravelAddressParser
{
    /**
     * Common street suffixes for address validation
     */
    private static array $streetSuffixes = [
        'ST',
        'STREET',
        'AVE',
        'AVENUE',
        'RD',
        'ROAD',
        'DR',
        'DRIVE',
        'LN',
        'LANE',
        'CT',
        'COURT',
        'CIR',
        'CIRCLE',
        'PL',
        'PLACE',
        'WAY',
        'BLVD',
        'BOULEVARD',
        'TER',
        'TERRACE',
        'LOOP',
        'PKWY',
        'PARKWAY',
        'TRL',
        'TRAIL',
        'PATH',
        'WALK',
        'ALY',
        'ALLEY',
        'HWY',
        'HIGHWAY',
        'EXPY',
        'EXPRESSWAY',
        'ROW',
        'RUN',
        'PASS',
    ];

    /**
     * Common apartment/unit indicators
     */
    private static array $unitIndicators = [
        '#',
        'APT',
        'APARTMENT',
        'SUITE',
        'STE',
        'UNIT',
        'FLOOR',
        'FL',
        'ROOM',
        'RM',
        'BLDG',
        'BUILDING',
        'LOT',
        'SPACE',
        'SPC',
    ];

    /**
     * Valid US state abbreviations
     */
    private static array $validStates = [
        'AL',
        'AK',
        'AZ',
        'AR',
        'CA',
        'CO',
        'CT',
        'DE',
        'DC',
        'FL',
        'GA',
        'HI',
        'ID',
        'IL',
        'IN',
        'IA',
        'KS',
        'KY',
        'LA',
        'ME',
        'MD',
        'MA',
        'MI',
        'MN',
        'MS',
        'MO',
        'MT',
        'NE',
        'NV',
        'NH',
        'NJ',
        'NM',
        'NY',
        'NC',
        'ND',
        'OH',
        'OK',
        'OR',
        'PA',
        'RI',
        'SC',
        'SD',
        'TN',
        'TX',
        'UT',
        'VT',
        'VA',
        'WA',
        'WV',
        'WI',
        'WY',
    ];

    /**
     * Parse a US address string into structured components
     *
     * Expected formats:
     * - "Street Address City, State ZIP"
     * - "Street Address, City, State ZIP"
     * - "Street Address, City, State ZIP-extended"
     * - "Street Address Unit, City, State ZIP"
     *
     * @param  string  $address  The address 1 liner string to parse
     * @return array{address1: string, address2: ?string, city: string, state: string, zip: string, county: null} Parsed address components with keys: address1, address2, city, state, zip, county
     *
     * @throws AddressParsingException If the address cannot be parsed or is invalid
     */
    public static function parseAddressString(string $address): array
    {
        // Input validation
        if (in_array(mb_trim($address), ['', '0'], true)) {
            throw new AddressParsingException('Address cannot be empty');
        }

        // Normalize the address string
        $address = self::normalizeAddress($address);

        // Extract state and ZIP from the end
        if (in_array(preg_match('/\b([A-Z]{2})\s+(\d{5}(?:-\d{4})?)$/i', $address, $matches, PREG_OFFSET_CAPTURE), [0, false], true)) {
            throw new AddressParsingException('Cannot find valid state and ZIP at the end of the address');
        }

        $stateOffset = $matches[0][1];
        $state = mb_strtoupper($matches[1][0]);
        $zip = $matches[2][0];

        if (! self::isValidState($state)) {
            throw new AddressParsingException("Invalid state abbreviation: '{$state}'");
        }

        if (! self::isValidZipCode($zip)) {
            throw new AddressParsingException("Invalid ZIP code: '{$zip}'");
        }

        // Get the part before state and ZIP
        $beforeState = mb_rtrim(mb_substr($address, 0, $stateOffset), ', ');

        if ($beforeState === '' || $beforeState === '0') {
            throw new AddressParsingException('Address missing components before state and ZIP');
        }

        // Split beforeState by commas
        $beforeParts = array_map('trim', explode(',', $beforeState));
        $numBefore = count($beforeParts);

        if ($numBefore === 1) {
            // No commas before state: parse street and city based on suffix
            $words = array_map('trim', explode(' ', $beforeState));
            $upperWords = array_map('mb_strtoupper', $words);

            // Find the rightmost street suffix
            $suffixIndex = -1;
            for ($i = count($upperWords) - 1; $i > 0; $i--) {
                if (in_array($upperWords[$i], self::$streetSuffixes)) {
                    $suffixIndex = $i;
                    break;
                }
            }

            if ($suffixIndex === -1) {
                throw new AddressParsingException('Could not identify street suffix in address without commas');
            }

            // Street is up to the suffix, city is after
            $streetWords = array_slice($words, 0, $suffixIndex + 1);
            $address1 = implode(' ', $streetWords);

            $cityWords = array_slice($words, $suffixIndex + 1);
            if ($cityWords === []) {
                throw new AddressParsingException('City cannot be empty');
            }
            $city = implode(' ', $cityWords);

            // Now separate possible unit from address1
            $separated = self::separateUnitFromAddress($address1);
            $address1 = $separated['address1'];
            $address2 = $separated['address2'];

            // Check if what we thought was city actually starts with a unit indicator (unit after suffix)
            $cityWords = explode(' ', $city);
            $firstWordUpper = mb_strtoupper($cityWords[0]);
            $unitPrefix = '';
            foreach (self::$unitIndicators as $ind) {
                $indUpper = mb_strtoupper((string) $ind);
                if (mb_strpos($firstWordUpper, $indUpper) === 0) {
                    $unitPrefix = $ind;
                    break;
                }
            }

            if ($unitPrefix !== '') {
                $unitParts = array_shift($cityWords); // e.g. Apt or #12
                // Only append second token if it contains at least one digit (unit number like 4B, 12, 2A)
                if (count($cityWords) > 0 && preg_match('/^(?=.*\d)[\w-]{1,10}$/i', $cityWords[0])) {
                    $unitParts .= ' '.array_shift($cityWords);
                }
                $address2 = $address2 ? $address2.' '.$unitParts : $unitParts;
                $city = implode(' ', $cityWords);
                if ($city === '' || $city === '0') {
                    throw new AddressParsingException('City cannot be empty after parsing unit');
                }
            }

            // Validate street address
            if (! self::isValidStreetAddress($address1)) {
                throw new AddressParsingException("Invalid street address format: '{$address1}'");
            }

            $addressInfo = [
                'address1' => $address1,
                'address2' => $address2,
            ];
        } else {
            // Commas present: last is city, before are address parts
            $city = array_pop($beforeParts);
            if (empty($city)) {
                throw new AddressParsingException('City cannot be empty');
            }
            $addressInfo = self::parseAddressLines($beforeParts);
        }

        return [
            'address1' => $addressInfo['address1'],
            'address2' => self::normalizeUnitIndicator($addressInfo['address2']),
            'city' => $city,
            'state' => $state,
            'zip' => $zip,
            'county' => null,
        ];
    }

    /**
     * Parse address string that may include county information
     *
     * Expected format: "Street Address, City, County, State ZIP"
     *
     * @param  string  $address  The address 1 liner string to parse
     * @return array{address1: string, address2: ?string, city: string, state: string, zip: string, county: ?string} Parsed address components including county
     *
     * @throws AddressParsingException If the address cannot be parsed
     */
    public static function parseAddressStringWithCounty(string $address): array
    {
        $address = self::normalizeAddress($address);
        $parts = array_map('trim', explode(',', $address));

        if (count($parts) < 3) {
            throw new AddressParsingException(
                'Address must contain at least 3 comma-separated parts'
            );
        }

        // Parse state and ZIP from the last part
        $stateZipPart = array_pop($parts);
        $stateZipInfo = self::parseStateAndZip($stateZipPart);

        $county = null;
        $city = null;

        if (count($parts) >= 3) {
            // Format: Street, City, County, State ZIP
            $county = array_pop($parts);
            $city = array_pop($parts);
        } else {
            // Format: Street, City, State ZIP
            $city = array_pop($parts);
        }

        if (empty($city)) {
            throw new AddressParsingException('City cannot be empty');
        }

        // Parse address lines from remaining parts
        $addressInfo = self::parseAddressLines($parts);

        return [
            'address1' => $addressInfo['address1'],
            'address2' => self::normalizeUnitIndicator($addressInfo['address2']),
            'city' => $city,
            'state' => $stateZipInfo['state'],
            'zip' => $stateZipInfo['zip'],
            'county' => $county,
        ];
    }

    /**
     * Validate a ZIP code format
     *
     * @param  string  $zip  The ZIP code to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidZipCode(string $zip): bool
    {
        return (bool) preg_match('/^\d{5}(?:-\d{4})?$/', $zip);
    }

    /**
     * Validate a state abbreviation
     *
     * @param  string  $state  The state abbreviation to validate
     * @return bool True if valid, false otherwise
     */
    public static function isValidState(string $state): bool
    {
        return in_array(mb_strtoupper($state), self::getValidStates());
    }

    /**
     * Get all valid state abbreviations
     *
     * @return array Array of valid state abbreviations
     */
    public static function getValidStates(): array
    {
        return self::$validStates;
    }

    /**
     * Format a parsed address back into a 1 liner string
     *
     * @param  array{address1: string, address2: ?string, city: string, state: string, zip: string}  $addressComponents  The parsed address components
     * @return string The formatted address string
     */
    public static function formatAddress(array $addressComponents): string
    {
        $parts = [];

        // Address line 1
        if (! empty($addressComponents['address1'])) {
            $addressLine = $addressComponents['address1'];

            // Add address line 2 if present
            if (! empty($addressComponents['address2'])) {
                $addressLine .= ' '.$addressComponents['address2'];
            }

            $parts[] = $addressLine;
        }

        // City
        if (! empty($addressComponents['city'])) {
            $parts[] = $addressComponents['city'];
        }

        // State and ZIP
        if (! empty($addressComponents['state']) && ! empty($addressComponents['zip'])) {
            $parts[] = $addressComponents['state'].' '.$addressComponents['zip'];
        }

        return implode(', ', $parts);
    }

    /**
     * Normalize address string by cleaning up whitespace and formatting
     */
    private static function normalizeAddress(string $address): string
    {
        // Trim and normalize whitespace
        $address = mb_trim($address);
        $address = preg_replace('/\s+/', ' ', $address);

        // Remove periods after common abbreviations
        $abbrevs = array_merge(self::$streetSuffixes, self::$unitIndicators, self::getValidStates());
        $pattern = '/\b('.implode('|', array_map('preg_quote', $abbrevs)).')\./i';
        $address = preg_replace($pattern, '$1', (string) $address);

        // Remove any trailing periods or commas
        $address = mb_rtrim($address, '.,');

        return $address;
    }

    /**
     * Parse state and ZIP code from the last part of the address
     *
     * @param  string  $stateZip  The state and ZIP portion
     * @return array Array with 'state' and 'zip' keys
     *
     * @throws AddressParsingException If format is invalid
     */
    private static function parseStateAndZip(string $stateZip): array
    {
        $stateZip = mb_trim($stateZip);
        $stateZipUpper = mb_strtoupper($stateZip);

        if (in_array(preg_match('/^([A-Z]{2})\s+(\d{5}(?:-\d{4})?)$/', $stateZipUpper, $matches), [0, false], true)) {
            throw new AddressParsingException(
                "Invalid state/ZIP format: '{$stateZip}'. Expected format: 'ST 12345' or 'ST 12345-6789'"
            );
        }

        $state = $matches[1];
        $zip = $matches[2];

        // Validate state abbreviation
        if (! self::isValidState($state)) {
            throw new AddressParsingException("Invalid state abbreviation: '{$state}'");
        }

        return ['state' => $state, 'zip' => $zip];
    }

    /**
     * Parse address lines into address1 and optional address2
     *
     * @param  array  $addressParts  Array of address parts
     * @return array Array with 'address1' and 'address2' keys
     *
     * @throws AddressParsingException If address is invalid
     */
    private static function parseAddressLines(array $addressParts): array
    {
        if ($addressParts === []) {
            throw new AddressParsingException('Street address cannot be empty');
        }

        $address1 = $addressParts[0];
        $address2 = null;

        // Validate that address1 looks like a street address
        if (! self::isValidStreetAddress($address1)) {
            throw new AddressParsingException("Invalid street address format: '{$address1}'");
        }

        // Check if address1 contains unit information that should be separated
        $separatedAddress = self::separateUnitFromAddress($address1);
        $address1 = $separatedAddress['address1'];
        $address2 = $separatedAddress['address2'];

        // If there are additional address parts, combine them as address2
        if (count($addressParts) > 1) {
            $additionalParts = implode(' ', array_slice($addressParts, 1)); // Use space instead of comma for combining
            $address2 = $address2 ? $address2.' '.$additionalParts : $additionalParts;
        }

        return [
            'address1' => $address1,
            'address2' => self::normalizeUnitIndicator($address2),
        ];
    }

    /**
     * Separate unit information from the main address line
     *
     * @param  string  $address  The address line
     * @return array Array with separated 'address1' and 'address2'
     */
    private static function separateUnitFromAddress(string $address): array
    {
        $address1 = $address;
        $address2 = null;

        // Pattern to match unit indicators at the end
        $unitPattern = '/\b('.implode('|', array_map('preg_quote', self::$unitIndicators)).')\b\s*(.+)$/i';

        if (preg_match($unitPattern, $address, $matches)) {
            $beforeUnit = mb_trim(mb_substr($address, 0, mb_strpos($address, $matches[0])));
            $unitInfo = mb_trim($matches[0]);

            if ($beforeUnit !== '' && $beforeUnit !== '0') {
                $address1 = $beforeUnit;
                $address2 = $unitInfo;
            }
        }

        return [
            'address1' => $address1,
            'address2' => $address2,
        ];
    }

    /**
     * Validate that a string looks like a valid street address
     *
     * @param  string  $address  The address to validate
     * @return bool True if valid, false otherwise
     */
    private static function isValidStreetAddress(string $address): bool
    {
        $address = mb_trim($address);

        if ($address === '' || $address === '0') {
            return false;
        }

        // Must contain at least one number (house number)
        if (in_array(preg_match('/\d/', $address), [0, false], true)) {
            return false;
        }

        $addressUpper = mb_strtoupper($address);
        $words = explode(' ', $addressUpper);

        // Check if it contains a recognized street suffix
        foreach (self::$streetSuffixes as $suffix) {
            if (in_array($suffix, $words)) {
                return true;
            }
        }

        // If no street suffix found, check if it starts with a number and has multiple words
        return preg_match('/^\d+/', $address) && count($words) >= 2;
    }

    /**
     * Normalize unit/apartment indicator casing.
     * Keeps leading '#' units as-is (e.g. #12) while uppercasing others (e.g. Apt 4B -> APT 4B).
     *
     * @param  string|null  $unit  The unit/apartment string
     * @return string|null The normalized unit/apartment string
     */
    private static function normalizeUnitIndicator(?string $unit): ?string
    {
        if ($unit === null) {
            return null;
        }
        $unit = mb_trim($unit);
        if ($unit === '') {
            return null;
        }
        if (mb_substr($unit, 0, 1) === '#') {
            return $unit; // Preserve #12 style
        }

        return mb_strtoupper($unit);
    }
}
