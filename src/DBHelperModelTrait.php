<?php

namespace Gecche\DBHelper;

use Gecche\DBHelper\Facades\DBHelper;

trait DBHelperModelTrait
{
    protected $dbAttributes = null;
    protected $dbHelper;


    public function getDBAttributes() {
        return is_null($this->dbAttributes)
            ? array_keys($this->setAttributesFromDB())
            : array_keys($this->dbAttributes);
    }

    protected function setAttributesFromDB() {
        $this->dbAttributes = $this->dbHelper()
            ->listColumnsDefault(null,true);

        return $this->dbAttributes;
    }

    public function getDBDefaults($key = null) {
        if (is_null($this->dbAttributes))
            $this->setAttributesFromDB();

        return $key
            ? array_get($this->dbAttributes,$key)
            : $this->dbAttributes;
    }

    public function dbHelper() {
        if (is_null($this->dbHelper)) {
            $this->dbHelper = DBHelper::helper($this->connection);
        }
        return $this->dbHelper;
    }




}
