<?php

namespace Awaisjameel\LaravelAddressParser\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Awaisjameel\LaravelAddressParser\LaravelAddressParser
 */
class LaravelAddressParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Awaisjameel\LaravelAddressParser\LaravelAddressParser::class;
    }
}
