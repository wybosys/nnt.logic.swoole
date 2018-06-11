<?php

namespace Nnt\Store;

abstract class Rdb extends Dbms
{
    /**
     * 查询并且返回查询结果
     * @param $cmd
     * @return object
     */
    abstract function query($cmd);

    /**
     * 执行命令，仅返回成功与否
     */
    abstract function execute($cmd): bool;
}