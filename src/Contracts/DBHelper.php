<?php

namespace Gecche\DBHelper\Contracts;

use Illuminate\Database\Connection;

interface DBHelper
{

    public function dataType($type);


    public function setTable($table);

    public function getTable();


    public function listColumnsDefault($table = null);


    public function listColumnsDatatypes($table = null);





}
