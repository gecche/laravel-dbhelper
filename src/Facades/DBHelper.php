<?php namespace Gecche\DBHelper\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Illuminate\Database\Schema\Builder
 */
class DBHelper extends Facade {


	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{

        return 'dbhelper';
    }

}
