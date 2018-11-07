<?php

namespace Nnt\Store;

use Nnt\Core\Variant;

abstract class Kv extends Dbms
{
    /**
     * @param string $key
     * @return Variant
     */
    abstract function get(string $key);

    /**
     * @param string $key
     * @param Variant $val
     * @return bool
     */
    abstract function set(string $key, Variant $val);

    /**
     * @param string $key
     * @param Variant $val
     * @return Variant
     */
    abstract function getset(string $key, Variant $val);

    /**
     * @param string $key
     * @return DbExecuteStat
     */
    abstract function del(string $key);

    // kv数据库通常没有自增函数，所以需要各个业务类自己实现
    abstract function autoinc(string $key, $delta);

    // 增加
    abstract function inc(string $key, $delta);

}