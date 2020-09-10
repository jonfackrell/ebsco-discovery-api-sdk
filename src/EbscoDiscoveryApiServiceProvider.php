<?php

namespace JonFackrell\Eds;

use Illuminate\Support\ServiceProvider;

class EbscoDiscoveryApiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        /*if (! class_exists('CreateEdsApiTable')) {
            $this->publishes([
                __DIR__.'/../database/migrations/create_eds_api_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_eds_api_table.php'),
            ], 'migrations');
        }*/

        $this->publishes([
            __DIR__.'/../config/ebsco-discovery.php' => base_path('config/ebsco-discovery.php'),
        ], 'config');
    }

    public function register()
    {
        $this->app->bind('ebsco-discovery', function () {
            return new Eds();
        });

        $this->mergeConfigFrom(__DIR__.'/../config/ebsco-discovery.php', 'ebsco-discovery');
    }
}
