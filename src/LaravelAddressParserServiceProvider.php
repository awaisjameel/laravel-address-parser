<?php

namespace Awaisjameel\LaravelAddressParser;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Awaisjameel\LaravelAddressParser\Commands\LaravelAddressParserCommand;

class LaravelAddressParserServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-address-parser')
            ->hasConfigFile();
    }
}