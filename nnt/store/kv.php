<?php

namespace Nnt\Store;

use Nnt\Core\Variant;

abstract class Kv extends Dbms
{
    abstract function get(string $key): Variant;

    abstract function set(string $key, Variant $val): bool;

    abstract function getset(string $key, Variant $val): Variant;

    abstract function del(string $key): DbExecuteStat;

    // kv数据库通常没有自增函数，所以需要各个业务类自己实现
    abstract function autoinc(string $ke, $delta);

    // 增加
    abstract function inc(string $key, $delta);

}