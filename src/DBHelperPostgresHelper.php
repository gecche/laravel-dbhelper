<?php

namespace Gecche\DBHelper;

use Gecche\DBHelper\Contracts\DBHelper as DBHelperContract;
use Illuminate\Cache\CacheManager;
use Illuminate\Database\Connection;
use Illuminate\Support\Arr;

class DBHelperPostgresHelper implements DBHelperContract
{
    protected $cache;
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
        static $types = array(
            // Integer
            'smallint' => array('type' => 'int', 'min' => '-32768', 'max' => '32767'),
            'int2' => array('type' => 'int', 'min' => '-32768', 'max' => '32767'),
            'integer' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
            'int' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
            'int4' => array('type' => 'int', 'min' => '-2147483648', 'max' => '2147483647'),
            'serial' => array('type' => 'int', 'min' => '1', 'max' => '2147483647'),
            'bigint' => array('type' => 'int', 'min' => '-9223372036854775808', 'max' => '9223372036854775807'),
            'int8' => array('type' => 'int', 'min' => '-9223372036854775808', 'max' => '9223372036854775807'),
            'bigserial' => array('type' => 'int', 'min' => '1', 'max' => '9223372036854775807'),

            // Numeric
            'decimal' => array('type' => 'float', 'exact' => true),
            'numeric' => array('type' => 'float', 'exact' => true),
            'real' => array('type' => 'float'),
            'float4' => array('type' => 'float'),
            'double precision' => array('type' => 'float'),
            'float8' => array('type' => 'float'),
            'money' => array('type' => 'float'),

            // Boolean
            'boolean' => array('type' => 'bool'),
            'bool' => array('type' => 'bool'),

            // String / text / datetime / misc
            'char' => array('type' => 'string', 'exact' => true),
            'character' => array('type' => 'string', 'exact' => true),
            'bpchar' => array('type' => 'string', 'exact' => true),
            'varchar' => array('type' => 'string'),
            'character varying' => array('type' => 'string'),
            'text' => array('type' => 'string'),
            'citext' => array('type' => 'string'),
            'uuid' => array('type' => 'string'),
            'xml' => array('type' => 'string'),
            'json' => array('type' => 'string'),
            'jsonb' => array('type' => 'string'),
            'date' => array('type' => 'string'),
            'time' => array('type' => 'string'),
            'time without time zone' => array('type' => 'string'),
            'timetz' => array('type' => 'string'),
            'time with time zone' => array('type' => 'string'),
            'timestamp' => array('type' => 'string'),
            'timestamp without time zone' => array('type' => 'string'),
            'timestamptz' => array('type' => 'string'),
            'timestamp with time zone' => array('type' => 'string'),
            'interval' => array('type' => 'string'),
            'inet' => array('type' => 'string'),
            'cidr' => array('type' => 'string'),
            'macaddr' => array('type' => 'string'),
            'bytea' => array('type' => 'string', 'binary' => true),
            'bit' => array('type' => 'string', 'exact' => true),
            'bit varying' => array('type' => 'string'),

            // Geometry-like types
            'point' => array('type' => 'string'),
            'line' => array('type' => 'string'),
            'lseg' => array('type' => 'string'),
            'box' => array('type' => 'string'),
            'path' => array('type' => 'string'),
            'polygon' => array('type' => 'string'),
            'circle' => array('type' => 'string'),
        );

        $type = strtolower(trim((string)$type));

        if (strpos($type, '_') === 0) {
            return array('type' => 'string');
        }

        if (!isset($types[$type])) {
            return array();
        }

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

    public function listColumnsDefault($table = null)
    {
        $table = $table ?: $this->getTable();
        list($schema, $tableName) = $this->parseSchemaAndTable($table);

        $cacheKey = $this->buildCacheKey(['listColumnsDefault', $schema, $tableName]);
        if ($cacheKey && $this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $result = $this->dbConnection->select(
            'SELECT column_name, data_type, udt_name, is_nullable, column_default
             FROM information_schema.columns
             WHERE table_schema = ? AND table_name = ?
             ORDER BY ordinal_position',
            [$schema, $tableName]
        );

        $columns = array();
        foreach ($result as $row) {
            $row = (array)$row;

            $rawType = strtolower(Arr::get($row, 'data_type', ''));
            $udtType = strtolower(Arr::get($row, 'udt_name', ''));
            $type = $rawType === 'user-defined' ? $udtType : $rawType;

            $column = $this->dataType($type);
            $column['column_default'] = Arr::get($row, 'column_default');

            $enumOptions = $this->listEnumValuesByType($udtType, $schema);
            if (!empty($enumOptions)) {
                $column['type'] = 'string';
                $column['options'] = array_combine($enumOptions, $enumOptions);

                if (Arr::get($row, 'is_nullable') === 'YES') {
                    $column['options'] = [-1 => null] + $column['options'];
                }
            }

            $columns[Arr::get($row, 'column_name')] = $column;
        }

        if ($cacheKey) {
            $this->cache->forever($cacheKey, $columns);
        }

        return $columns;
    }

    public function listColumnsDatatypes($table = null)
    {
        $columns = $this->listColumnsDefault($table);
        return array_map(function ($column) {
            return Arr::except($column, ['column_default', 'options']);
        }, $columns);
    }

    protected function parseSchemaAndTable($table)
    {
        $table = (string)$table;
        if (strpos($table, '.') !== false) {
            return explode('.', $table, 2);
        }

        return ['public', $table];
    }

    protected function listEnumValuesByType($enumType, $schema)
    {
        if (!$enumType) {
            return [];
        }

        $result = $this->dbConnection->select(
            'SELECT e.enumlabel
             FROM pg_type t
             INNER JOIN pg_enum e ON t.oid = e.enumtypid
             INNER JOIN pg_namespace n ON n.oid = t.typnamespace
             WHERE t.typname = ? AND n.nspname = ?
             ORDER BY e.enumsortorder',
            [$enumType, $schema]
        );

        if (empty($result)) {
            return [];
        }

        return array_map(function ($row) {
            return Arr::get((array)$row, 'enumlabel');
        }, $result);
    }

    protected function buildCacheKey($params)
    {
        return $this->useCache
            ? md5($this->connectionName . serialize($params))
            : false;
    }
}
