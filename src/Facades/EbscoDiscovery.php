<?php


namespace JonFackrell\Eds\Facades;


use Illuminate\Support\Facades\Facade;

class EbscoDiscovery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'eds';
    }
}