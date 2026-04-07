<?php

namespace Gecche\DBHelper;

use Illuminate\Cache\CacheManager;
use \Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Manager;
use Gecche\DBHelper\Contracts\DBHelper as DBHelperContract;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DBHelperMysqlHelper implements DBHelperContract
{
    /*
         * Metodi per popolare i form automaticamente dal database, funzionano solo con Mysql al momento.
         */

    protected $resolver;
    protected $config;
    protected $cache;
    protected $dbConfig;

    protected $useCache = false;

    protected $table = null;

    protected $connectionName;
    protected $dbConnection;

    public function __construct($connectionName, Connection $dbConnection, CacheManager $cacheManager, $useCache)
    {
        $this->connectionName = $connectionName;
        $this->dbConnection = $dbConnection;
        $this->cache = $cacheManager;
        $this->useCache = $useCache;


    }


    public function dataType($type)
    {
        static $types = array
        (
            // MySQL
            'blob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '65535'),
            'bool' => array('type' => 'bool'),
            'bigint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '18446744073709551615'),
            'bigserial' => array('type' => 'int', 'min' => '0', 'max' => '18446744073709551615'),
            'datetime' => array('type' => 'string'),
            'decimal unsigned' => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
            'double' => array('type' => 'float'),
            'double precision unsigned' => array('type' => 'float', 'min' => '0'),
            'double unsigned' => array('type' => 'float', 'min' => '0'),
            'enum' => array('type' => 'string'),
            'fixed' => array('type' => 'float', 'exact' => TRUE),
            'fixed unsigned' => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
            'float unsigned' => array('type' => 'float', 'min' => '0'),
            'geometry' => array('type' => 'string', 'binary' => TRUE),
            'geometrycollection' => array('type' => 'string', 'binary' => TRUE),
            'int unsigned' => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
            'integer unsigned' => array('type' => 'int', 'min' => '0', 'max' => '4294967295'),
            'json' => array('type' => 'string'),
            'linestring' => array('type' => 'string', 'binary' => TRUE),
            'longblob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '4294967295'),
            'longtext' => array('type' => 'string', 'character_maximum_length' => '4294967295'),
            'mediumblob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '16777215'),
            'mediumint' => array('type' => 'int', 'min' => '-8388608', 'max' => '8388607'),
            'mediumint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '16777215'),
            'mediumtext' => array('type' => 'string', 'character_maximum_length' => '16777215'),
            'multilinestring' => array('type' => 'string', 'binary' => TRUE),
            'multipoint' => array('type' => 'string', 'binary' => TRUE),
            'multipolygon' => array('type' => 'string', 'binary' => TRUE),
            'national varchar' => array('type' => 'string'),
            'numeric unsigned' => array('type' => 'float', 'exact' => TRUE, 'min' => '0'),
            'nvarchar' => array('type' => 'string'),
            'point' => array('type' => 'string', 'binary' => TRUE),
            'polygon' => array('type' => 'string', 'binary' => TRUE),
            'real unsigned' => array('type' => 'float', 'min' => '0'),
            'set' => array('type' => 'string'),
            'serial' => array('type' => 'int', 'min' => '0', 'max' => '18446744073709551615'),
            'smallint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '65535'),
            'text' => array('type' => 'string', 'character_maximum_length' => '65535'),
            'tinyblob' => array('type' => 'string', 'binary' => TRUE, 'character_maximum_length' => '255'),
            'tinyint' => array('type' => 'int', 'min' => '-128', 'max' => '127'),
            'tinyint unsigned' => array('type' => 'int', 'min' => '0', 'max' => '255'),
            'tinytext' => array('type' => 'string', 'character_maximum_length' => '255'),
            'year' => array('type' => 'string'),
            // SQL-92
            'bit' => array('type' => 'string', 'exact' => TRUE),
            'bit varying' => array('type' => 'string'),
            'char' => array('type' => 'string', 'exact' => TRUE),
            'char varying' => array('type' => 'string'),
            'character' => array('type' => 'string', 'exact' => TRUE),
            'character varying' => array('type' => 'string'),
            'date' => array('type' => 'string'),
            'dec' => array('type' => 'float', 'exact' => TRUE),
            'decimal' => array('type' => 'float', 'exact' => TRUE),
            'double precision' => array('type' => 'float'),
            'float' => array('type' => 'float'),
            'int' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
            'integer' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
            'interval' => array('type' => 'string'),
            'national char' => array('type' => 'string', 'exact' => TRUE),
            'national char varying' => array('type' => 'string'),
            'national character' => array('type' => 'string', 'exact' => TRUE),
            'national character varying' => array('type' => 'string'),
            'nchar' => array('type' => 'string', 'exact' => TRUE),
            'nchar varying' => array('type' => 'string'),
            'numeric' => array('type' => 'float', 'exact' => TRUE),
            'real' => array('type' => 'float'),
            'smallint' => array('type' => 'int', 'min' => '-32768', 'max' => '32767'),
            'time' => array('type' => 'string'),
            'time with time zone' => array('type' => 'string'),
            'timestamp' => array('type' => 'string'),
            'timestamp with time zone' => array('type' => 'string'),
            'varchar' => array('type' => 'string'),
            // SQL:1999
            'binary large object' => array('type' => 'string', 'binary' => TRUE),
            'boolean' => array('type' => 'bool'),
            'char large object' => array('type' => 'string'),
            'character large object' => array('type' => 'string'),
            'clob' => array('type' => 'string'),
            'national character large object' => array('type' => 'string'),
            'nchar large object' => array('type' => 'string'),
            'nclob' => array('type' => 'string'),
            'time without time zone' => array('type' => 'string'),
            'timestamp without time zone' => array('type' => 'string'),
            // SQL:2003
            'bigint' => array('type' => 'int', 'min' => '-9223372036854775808', 'max' => '9223372036854775807'),
            // SQL:2008
            'binary' => array('type' => 'string', 'binary' => TRUE, 'exact' => TRUE),
            'binary varying' => array('type' => 'string', 'binary' => TRUE),
            'varbinary' => array('type' => 'string', 'binary' => TRUE),);

        $type = str_replace(' zerofill', '', $type);

        if (!isset($types[$type]))
            return array();


        return $types[$type];
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    protected function parseType($type)
    {
        if (($open = strpos($type, '(')) === FALSE) {
            // No length specified
            return array($type, NULL);
        }

        // Closing parenthesis
        $close = strrpos($type, ')', $open);

        // Length without parentheses
        $length = substr($type, $open + 1, $close - 1 - $open);

        // Type without the length
        $type = substr($type, 0, $open) . substr($type, $close + 1);

        return array($type, $length);
    }

    public function listColumnsDefault($table = null)
    {

        $table = $table ?: $this->getTable();
        $cacheKey = $this->buildCacheKey(['listColumnsDefault', $table]);
        if ($cacheKey && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }
        $result = $this->dbConnection->select('SHOW COLUMNS FROM ' . $table);

        $columns = array();
        foreach ($result as $row) {
            $row = (array)$row;


            list($type, $length) = $this->parseType($row['Type']);


            $column = $this->dataType($type);
            $column['column_default'] = Arr::get($row, 'Default');

            switch (Arr::get($column, 'type')) {
                case 'int':
                    switch ($type) {
                        case 'tinyint':
                            if (isset($length) && $length < 2 && $length > 0) {
                                $column['options'] = array(
                                    0 => 0,
                                    1 => 1
                                );
                                if (Arr::get($row, 'Null', 'YES') == 'YES') {
                                    $column['options'] = [-1 => null] + $column['options'];
                                }
                            }
                            break;
                    }
                    break;
                case 'string':
                    switch ($type) {
                        case 'enum':
                        case 'set':
                            $options = explode('\',\'', substr($length, 1, -1));
                            $column['options'] = array_combine($options, $options);


                            if (Arr::get($row, 'Null', 'YES') == 'YES') {
                                $column['options'] = [-1 => null] + $column['options'];
                            }

                            break;
                    }
                    break;
            }

            $columns[$row['Field']] = $column;
        }

        if ($cacheKey)
            $this->cache->forever($cacheKey, $columns);

        return $columns;
    }

    public function listColumnsDatatypes($table = null)
    {
        $columns = $this->listColumnsDefault($table);
        return array_map(function ($column) {
            return Arr::except($column, ['column_default', 'options']);
        }, $columns);
    }


    protected function buildCacheKey($params)
    {
        return $this->useCache
            ? md5($this->connectionName . serialize($params))
            : false;
    }

}
