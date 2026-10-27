<?php

use Awaisjameel\LaravelAddressParser\Facades\LaravelAddressParser;
use Awaisjameel\LaravelAddressParser\LaravelAddressParserServiceProvider;
use Illuminate\Support\ServiceProvider;

it('resolves the parser through its Laravel facade', function () {
    expect(LaravelAddressParser::parseAddressString('123 Main St, Springfield, IL 62704'))
        ->toMatchArray(['address1' => '123 Main St', 'city' => 'Springfield', 'state' => 'IL']);
});

it('loads and publishes configuration under the documented tag', function () {
    $paths = ServiceProvider::pathsToPublish(LaravelAddressParserServiceProvider::class, 'address-parser-config');

    expect(config('address-parser'))->toBe([])
        ->and(array_map(realpath(...), array_keys($paths)))->toContain(realpath(__DIR__.'/../config/address-parser.php'))
        ->and(array_values($paths))->toContain(config_path('address-parser.php'));
});
