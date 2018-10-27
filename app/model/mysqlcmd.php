<?php

namespace App\Model;

/**
 * @model()
 */
class MysqlCmd
{
    /**
     * @string(1, [input], "sql")
     */
    public $sql;

    /**
     * @array(2, json, [output], "返回数据")
     */
    public $result;
}

