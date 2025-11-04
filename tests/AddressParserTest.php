<?php

use Awaisjameel\LaravelAddressParser\AddressParsingException;
use Awaisjameel\LaravelAddressParser\LaravelAddressParser;

// -----------------------------
// Helpers / Datasets
// -----------------------------

dataset('valid_addresses_no_commas', [
    // Street suffix present, city after suffix
    '123 Main St Springfield IL 62704' => [
        '123 Main St Springfield IL 62704',
        [
            'address1' => '123 Main St',
            'address2' => null,
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62704',
            'county' => null,
        ],
    ],
    // Unit directly after street suffix, no commas
    '500 Elm Avenue Apt 4B Metropolis NY 10001' => [
        '500 Elm Avenue Apt 4B Metropolis NY 10001',
        [
            'address1' => '500 Elm Avenue',
            'address2' => 'APT 4B',
            'city' => 'Metropolis',
            'state' => 'NY',
            'zip' => '10001',
            'county' => null,
        ],
    ],
    // Unit indicator with #
    '77 Broadway St #12 Gotham NJ 07001' => [
        '77 Broadway St #12 Gotham NJ 07001',
        [
            'address1' => '77 Broadway St',
            'address2' => '#12',
            'city' => 'Gotham',
            'state' => 'NJ',
            'zip' => '07001',
            'county' => null,
        ],
    ],
]);

dataset('valid_addresses_with_commas', [
    'Simple with commas' => [
        '123 Main St, Springfield, IL 62704',
        [
            'address1' => '123 Main St',
            'address2' => null,
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62704',
            'county' => null,
        ],
    ],
    'With unit separated by comma' => [
        '500 Elm Avenue, Apt 4B, Metropolis, NY 10001',
        [
            'address1' => '500 Elm Avenue',
            'address2' => 'APT 4B',
            'city' => 'Metropolis',
            'state' => 'NY',
            'zip' => '10001',
            'county' => null,
        ],
    ],
    'Extended ZIP' => [
        '1600 Pennsylvania Avenue NW, Washington, DC 20500-0003',
        [
            'address1' => '1600 Pennsylvania Avenue NW',
            'address2' => null,
            'city' => 'Washington',
            'state' => 'DC',
            'zip' => '20500-0003',
            'county' => null,
        ],
    ],
    'Periods in abbreviations should be normalized' => [
        '123 Main St., Springfield, IL 62704',
        [
            'address1' => '123 Main St',
            'address2' => null,
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62704',
            'county' => null,
        ],
    ],
]);

dataset('valid_addresses_with_county', [
    'With County' => [
        '123 Main St, Springfield, Greene, MO 65804',
        [
            'address1' => '123 Main St',
            'address2' => null,
            'city' => 'Springfield',
            'state' => 'MO',
            'zip' => '65804',
            'county' => 'Greene',
        ],
    ],
    'With County and Unit' => [
        '500 Elm Avenue Apt 4B, Metropolis, Queens, NY 10001',
        [
            'address1' => '500 Elm Avenue',
            'address2' => 'APT 4B',
            'city' => 'Metropolis',
            'state' => 'NY',
            'zip' => '10001',
            'county' => 'Queens',
        ],
    ],
]);

dataset('invalid_addresses_parseAddressString', [
    'Empty String' => '',
    'Only Zero' => '0',
    'Missing State/Zip' => '123 Main St Springfield',
    'Invalid State' => '123 Main St Springfield ZZ 62704',
    'Invalid Zip' => '123 Main St Springfield IL 6270',
    'No Street Suffix No Commas' => '123 Springfield IL 62704', // fails street suffix detection before city
]);

dataset('invalid_addresses_with_county', [
    'Too Few Parts' => '123 Main St, IL 62704', // Only street + state/zip
    'Bad State Zip Format' => '123 Main St, Springfield, ILL 62704',
]);

dataset('zip_codes_valid', [
    'Standard' => '12345',
    'Extended' => '12345-6789',
]);

dataset('zip_codes_invalid', [
    'Too Short' => '1234',
    'Too Long' => '123456',
    'Bad Extended' => '12345-678',
    'Letters' => '12A45',
]);

dataset('state_codes_valid', [
    'CA',
    'NY',
    'TX',
    'DC',
]);

dataset('state_codes_invalid', [
    'Cali',
    'XX',
    'A',
    '',
    'ZZ',
]);

// -----------------------------
// Tests: parseAddressString (valid cases without commas)
// -----------------------------
it('parses valid addresses without commas', function (string $raw, array $expected) {
    $parsed = LaravelAddressParser::parseAddressString($raw);
    expect($parsed)->toMatchArray($expected);
})->with('valid_addresses_no_commas');

// -----------------------------
// Tests: parseAddressString (valid cases with commas)
// -----------------------------
it('parses valid addresses with commas', function (string $raw, array $expected) {
    $parsed = LaravelAddressParser::parseAddressString($raw);
    expect($parsed)->toMatchArray($expected);
})->with('valid_addresses_with_commas');

// -----------------------------
// Tests: parseAddressString exceptions
// -----------------------------
it('throws on invalid parseAddressString inputs', function (string $raw) {
    LaravelAddressParser::parseAddressString($raw);
})->with('invalid_addresses_parseAddressString')->throws(AddressParsingException::class);

// -----------------------------
// Tests: parseAddressStringWithCounty
// -----------------------------
it('parses valid addresses with county', function (string $raw, array $expected) {
    $parsed = LaravelAddressParser::parseAddressStringWithCounty($raw);
    expect($parsed)->toMatchArray($expected);
})->with('valid_addresses_with_county');

it('parses valid address without county using county method', function () {
    $parsed = LaravelAddressParser::parseAddressStringWithCounty('1600 Pennsylvania Avenue NW, Washington, DC 20500');
    expect($parsed)->toMatchArray([
        'address1' => '1600 Pennsylvania Avenue NW',
        'address2' => null,
        'city' => 'Washington',
        'state' => 'DC',
        'zip' => '20500',
        'county' => null,
    ]);
});

it('throws on invalid parseAddressStringWithCounty inputs', function (string $raw) {
    LaravelAddressParser::parseAddressStringWithCounty($raw);
})->with('invalid_addresses_with_county')->throws(AddressParsingException::class);

// -----------------------------
// Tests: ZIP validation
// -----------------------------
it('validates zip codes (valid)', function (string $zip) {
    expect(LaravelAddressParser::isValidZipCode($zip))->toBeTrue();
})->with('zip_codes_valid');

it('validates zip codes (invalid)', function (string $zip) {
    expect(LaravelAddressParser::isValidZipCode($zip))->toBeFalse();
})->with('zip_codes_invalid');

// -----------------------------
// Tests: State validation
// -----------------------------
it('validates state codes (valid)', function (string $state) {
    expect(LaravelAddressParser::isValidState($state))->toBeTrue();
})->with('state_codes_valid');

it('validates state codes (invalid)', function (string $state) {
    expect(LaravelAddressParser::isValidState($state))->toBeFalse();
})->with('state_codes_invalid');

// -----------------------------
// Tests: getValidStates
// -----------------------------
it('returns list of valid states containing CA', function () {
    $states = LaravelAddressParser::getValidStates();
    expect($states)->toContain('CA')->and(count($states))->toBeGreaterThan(40);
});

// -----------------------------
// Tests: formatAddress
// -----------------------------
it('formats address without address2', function () {
    $formatted = LaravelAddressParser::formatAddress([
        'address1' => '123 Main St',
        'address2' => null,
        'city' => 'Springfield',
        'state' => 'IL',
        'zip' => '62704',
    ]);
    expect($formatted)->toBe('123 Main St, Springfield, IL 62704');
});

it('formats address with address2', function () {
    $formatted = LaravelAddressParser::formatAddress([
        'address1' => '123 Main St',
        'address2' => 'APT 4B',
        'city' => 'Springfield',
        'state' => 'IL',
        'zip' => '62704',
    ]);
    expect($formatted)->toBe('123 Main St APT 4B, Springfield, IL 62704');
});

it('ignores missing components gracefully in formatAddress', function () {
    $formatted = LaravelAddressParser::formatAddress([
        'address1' => '',
        'address2' => null,
        'city' => 'Springfield',
        'state' => 'IL',
        'zip' => '62704',
    ]);
    expect($formatted)->toBe('Springfield, IL 62704');
});

// -----------------------------
// Edge cases: normalization (period removal) + trailing spaces
// -----------------------------
it('normalizes periods in abbreviations during parsing', function () {
    $parsed = LaravelAddressParser::parseAddressString('77 Broadway St. Gotham NJ 07001');
    expect($parsed['address1'])->toBe('77 Broadway St');
});

it('parses address with excess whitespace', function () {
    $parsed = LaravelAddressParser::parseAddressString('  123   Main   St   Springfield   IL   62704   ');
    expect($parsed)->toMatchArray([
        'address1' => '123 Main St',
        'address2' => null,
        'city' => 'Springfield',
        'state' => 'IL',
        'zip' => '62704',
        'county' => null,
    ]);
});
