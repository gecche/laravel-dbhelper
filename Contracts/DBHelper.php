<?php

namespace Gecche\DBHelper\Contracts;

use Illuminate\Database\Connection;

interface DBHelper
{

    public function dataType($type);

    public function migrationDataType($type);

    public function setTable($table);

    public function getTable();

    public function listTables();


    public function listColumns($table = null);


    public function listColumnsDefault($table = null, $raw = false, $single_column = null);


    public function listColumnsDatatypes($table = null);


    public function listColumnsMigrationDatatypes($table = null);



    public function listEnumValues($column, $table = null);



    public function getLangFields($table = null, $lang = null);


    public function getDBLangFields($lang = null);

}
