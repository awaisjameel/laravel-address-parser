<?php

namespace Awaisjameel\LaravelAddressParser\Tests;

use Awaisjameel\LaravelAddressParser\LaravelAddressParserServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LaravelAddressParserServiceProvider::class,
        ];
    }
}
