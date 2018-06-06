<?php

namespace Nnt\Core;

// 模型的原型信息
class ModelInfo
{

}

class Proto
{
    private static $_clazzes = [];

    /**
     * 解析模型的定义信息
     * @param $obj
     * @return ModelInfo 模型信息
     */
    static function Get($obj): ModelInfo
    {
        $clazz = get_class($obj);
        if ($clazz === false)
            return null;
        if (isset(self::$_clazzes[$clazz]))
            return self::$_clazzes[$clazz];
        $info = self::ParseClass($clazz);
        self::$_clazzes[$clazz] = $info;
        return $info;
    }

    static function ParseClass($clazz): ModelInfo
    {
        return null;
    }

    static function Output($obj)
    {
        return null;
    }

    static function CheckInputStatus($proto, $params): int
    {
        return STATUS::OK;
    }

    static function CheckInput($proto, $params): bool
    {
        return true;
    }
}