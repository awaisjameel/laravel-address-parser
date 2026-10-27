<?php

namespace Awaisjameel\LaravelAddressParser\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @mixin \Awaisjameel\LaravelAddressParser\LaravelAddressParser
 *
 * @see \Awaisjameel\LaravelAddressParser\LaravelAddressParser
 */
class LaravelAddressParser extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Awaisjameel\LaravelAddressParser\LaravelAddressParser::class;
    }
}
