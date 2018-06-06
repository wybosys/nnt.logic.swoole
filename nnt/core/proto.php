<?php

namespace Nnt\Core;

class FieldInfo
{
    // 唯一序号，后续类似pb的协议会使用id来做数据版本兼容
    public $id;

    // 可选
    public $optional;

    // 读取控制
    public $input;
    public $output;

    // 类型标签
    public $array;
    public $map;
    public $multimap;
    public $string;
    public $integer;
    public $double;
    public $boolean;
    public $enum;
    public $file;
    public $json;

    // 关联类型
    public $keytype;
    public $valtype;

    // 注释
    public $comment;

    // 有效性检查函数
    public $valid;
}

// 模型的原型信息
class ModelInfo
{
    // 需要登陆验证
    public $auth;

    // 是否是枚举类型，因为语言限制，无法对enum对象添加decorate处理，只能在服务器端使用class来模拟
    public $enum;

    // 用来定义常量，或者模拟str的枚举
    public $constant;

    // 隐藏后就不会加入到models列表中
    public $hidden;

    // 父类，目前用来生成api里面的父类名称
    public $parent;

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