<?php

namespace JonFackrell\Eds;

use Illuminate\Support\ServiceProvider;

class EbscoDiscoveryApiServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if (! class_exists('CreateEdsApiTable')) {
            $this->publishes([
                __DIR__.'../database/migrations/create_eds_api_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', time()).'_create_eds_api_table.php'),
            ], 'migrations');
        }
    }

    public function register()
    {
        $this->app->bind('ebsco-discovery', function () {
            return new Eds();
        });
    }
}
