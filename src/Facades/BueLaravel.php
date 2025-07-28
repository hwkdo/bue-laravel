<?php

namespace Hwkdo\BueLaravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Hwkdo\BueLaravel\BueLaravel
 */
class BueLaravel extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Hwkdo\BueLaravel\BueLaravel::class;
    }
}
