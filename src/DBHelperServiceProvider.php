<?php

namespace Gecche\DBHelper;

use Gecche\Breeze\Console\CompileRelationsCommand;
use Gecche\Breeze\DBHelpers\DBHelpersManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
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
