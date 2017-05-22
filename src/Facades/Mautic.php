<?php namespace Princealikhan\Mautic\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Mautic
 * @package Princealikhan\Mautic\Facades
 *
 * @method static connection($name)
 */
class Mautic extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'mautic';
    }
}