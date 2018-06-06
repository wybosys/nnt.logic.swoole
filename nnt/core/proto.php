<?php

namespace Nnt\Core;

class FieldInfo
{
    // 唯一序号，后续类似pb的协议会使用id来做数据版本兼容
    public $id;
    public $name;

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

    // 所有的数据项
    public $fields = [];
}

function model($options, $parent): ModelInfo
{
    $ret = new ModelInfo();
    $ret->auth = in_array('auth', $options);
    $ret->enum = in_array('enumm', $options);
    $ret->constant = in_array('constant', $options);
    $ret->hidden = in_array('hidden', $options);
    $ret->parent = $parent;
    return $ret;
}

function field_($id, $opts, $comment): FieldInfo
{
    $ret = new FieldInfo();
    $ret->id = $id;
    $ret->input = in_array('input', $opts);
    $ret->output = in_array('output', $opts);
    $ret->optional = in_array('optional', $opts);
    $ret->comment = $comment;
    return $ret;
}

function string_($id, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->string = true;
    return $ret;
}

function boolean_($id, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->boolean = true;
    return $ret;
}

function integer_($id, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->integer = true;
    return $ret;
}

function double_($id, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->double = true;
    return $ret;
}

function array_($id, $clz, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->array = true;
    $ret->valtype = $clz;
    return $ret;
}

function map_($id, $keytyp, $valtyp, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->map = true;
    $ret->keytype = $keytyp;
    $ret->valtype = $valtyp;
    return $ret;
}

function multimap_($id, $keytyp, $valtyp, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->multimap = true;
    $ret->keytype = $keytyp;
    $ret->valtype = $valtyp;
    return $ret;
}

function json_($id, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->json = true;
    return $ret;
}

function type_($id, $clz, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->valtype = $clz;
    return $ret;
}

function enumerate_($id, $clz, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->enum = true;
    $ret->valtype = $clz;
    return $ret;
}

function file_($id, $opts, $comment): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->file = true;
    return $ret;
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
        $clazz = is_object($obj) ? get_class($obj) : $obj;
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
        $reflect = new \ReflectionClass($clazz);
        // 提取model的信息
        $plain = $reflect->getDocComment();
        if (!preg_match('/@model\((\[.*\])?[, ]*(.*)\)/', $plain, $matches))
            return null;

        $ret = null;
        if (!$matches[1])
            $matches[1] = '[]';
        eval("\$ret = call_user_func('\Nnt\Core\model', $matches[1], '$matches[2]');");

        $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
        foreach ($props as $pname => $pinfo) {
            $plain = $pinfo->getDocComment();
            // 给所有类加上‘’，然后再调用函数
            if (!preg_match('/@([a-zA-Z]+)\((.+)\)/', $plain, $matches))
                continue;
            $func = '\Nnt\Core\\' . $matches[1] . "_";
            $args = preg_replace('/((?:\\\\[a-zA-Z]+)+)/', "'$0'", $matches[2]);

            $fi = null;
            eval("\$fi = call_user_func($func, $args);");
            $fi->name = $pname;
            $ret->fields[$fi->name] = $fi;
        }

        return $ret;
    }

    static function Output($obj)
    {
        return null;
    }

    static function CheckInputStatus($clazz, $params): int
    {
        $mi = self::Get($clazz);
        if ($mi == null)
            return STATUS::OK;
        foreach ($mi->fields as $fnm => $finfo) {
            if (!$finfo->input)
                continue;
            if ($finfo->optional)
                continue;
            $inp = ObjectT::Get($params, $fnm, null);
            if ($inp === null)
                return STATUS::PARAMETER_NOT_MATCH;
        }
        return STATUS::OK;
    }

    static function CheckInput($proto, $params): bool
    {
        return true;
    }
}