<?php

namespace Gecche\DBHelper;

use Illuminate\Support\ServiceProvider;

class DBHelperServiceProvider extends ServiceProvider
{


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('dbhelper', function ($app) {
            return new DBHelperManager($app);
        });

    }



}
