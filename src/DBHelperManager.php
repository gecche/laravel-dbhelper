<?php

namespace Gecche\DBHelper;

use Gecche\ModelPlus\ModelPlus;
use \Illuminate\Database\Connection;
use Illuminate\Support\Str;
use InvalidArgumentException;

class DBHelperManager
{


    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;


    /**
     * The array of created "helpers".
     *
     * @var array
     */
    protected $helpers = [];


    protected $config;
    protected $dbConfig;


    /**
     * Create a new manager instance.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;

        $this->config = $this->app['config']['dbhelper'] ?: [];
        $this->dbConfig = $this->app['config']['database'] ?: [];

    }



    public function createMysqlHelper($connectionName)
    {

        $dbConnection = $this->app['db']->connection($connectionName);

        return new DBHelperMysqlHelper($connectionName, $dbConnection, $this->app->make('cache'), array_get($this->config,'cache',false));
    }


    /**
     * @param string $connectionName
     * @return mixed
     */
    public function helper($connectionName = null)
    {
        if (is_null($connectionName)) {
            $connectionName = array_get($this->dbConfig, 'default');
        }

        if (is_null($connectionName)) {
            throw new \InvalidArgumentException("Connection name is required.");
        }

        $connections = array_get($this->dbConfig, 'connections', []);

        $connectionData = array_get($connections, $connectionName, []);

        $driverName = array_get($connectionData, 'driver');

        if (is_null($driverName)) {
            throw new \InvalidArgumentException("Driver name is required.");
        }

        if (!isset($this->helpers[$connectionName])) {
            $this->helpers[$connectionName] = $this->createHelper($connectionName,$driverName);
        }


        return $this->helpers[$connectionName];

    }


    /**
     * Create a new helper instance.
     *
     * @param  string $connectionName
     * @param  string $driverName
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createHelper($connectionName,$driverName)
    {
        // We'll check to see if a creator method exists for the given driver.
        $method = 'create' . Str::studly($driverName) . 'Helper';

        if (method_exists($this, $method)) {
            return $this->$method($connectionName);
        }

        throw new InvalidArgumentException("Helper [$driverName] not supported.");
    }

    /**
     * Get all of the created "helpers".
     *
     * @return array
     */
    public function getHelpers()
    {
        return $this->helpers;
    }


}
