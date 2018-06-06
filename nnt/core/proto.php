<?php

namespace Nnt\Core;

use Nnt\Logger\Logger;

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

    /**
     * @return FieldInfo
     */
    function field($nm)
    {
        return @$this->fields[$nm];
    }
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
        $sta = self::CheckInputStatus($proto, $params);
        return $sta == STATUS::OK;
    }

    const POD_TYPES = ['string', 'integer', 'double', 'boolean'];

    static function DecodeValue(FieldInfo $fp, $val, $input = true, $output = false)
    {
        if ($fp->valtype) {
            if ($fp->array) {
                $arr = [];
                if ($val) {
                    if (in_array($fp->valtype, self::POD_TYPES)) {
                        if (!(is_array($val))) {
                            // 对于array，约定用，来分割
                            $val = explode(",", $val);
                        }
                        if ($fp->valtype == "string") {
                            foreach ($val as $e) {
                                $arr[] = $e ? (string)$e : null;
                            }
                        } else if ($fp->valtype == "integer") {
                            foreach ($val as $e) {
                                $arr[] = (int)$e;
                            }
                        } else if ($fp->valtype == "double") {
                            foreach ($val as $e) {
                                $arr[] = (double)$e;
                            }
                        } else if ($fp->valtype == "boolean") {
                            foreach ($val as $e) {
                                $arr[] = !!$e;
                            }
                        }
                    } else {
                        if (is_string($val))
                            $val = json_decode($val);
                        if (is_array($val)) {
                            $clz = $fp->valtype;
                            foreach ($val as $e) {
                                $t = new $clz();
                                self::Decode($t, $e, $input, $output);
                                $arr[] = $t;
                            }
                        } else {
                            Logger::Log("Array遇到了错误的数据 $val");
                        }
                    }
                }
                return $arr;
            } else if ($fp->map) {
                $map = [];
                if (in_array($fp->valtype, self::POD_TYPES)) {
                    if ($fp->valtype == "string") {
                        foreach ($val as $ek => $ev) {
                            $map[$ek] = $ev ? (string)$ev : null;
                        }
                    } else if ($fp->valtype == "integer") {
                        foreach ($val as $ek => $ev) {
                            $map[$ek] = $ev ? (int)$ev : null;
                        }
                    } else if ($fp->valtype == "double") {
                        foreach ($val as $ek => $ev) {
                            $map[$ek] = $ev ? (double)$ev : null;
                        }
                    } else if ($fp->valtype == "boolean") {
                        foreach ($val as $ek => $ev) {
                            $map[$ek] = !!$ev;
                        }
                    }
                } else {
                    $clz = $fp->valtype;
                    foreach ($val as $ek => $ev) {
                        $t = new $clz();
                        self::Decode($t, $ev, $input, $output);
                        $map[$ek] = $t;
                    }
                }
                return $map;
            } else if ($fp->enum) {
                return (int)$val;
            } else {
                if (!in_array($fp->valtype, self::POD_TYPES))
                    $val = json_decode($val);
                if ($fp->valtype == "object")
                    return $val;
                $clz = $fp->valtype;
                $t = new $clz();
                self::Decode($t, $val, $input, $output);
                return $t;
            }
        } else {
            if ($fp->string)
                return $val ? (string)$val : null;
            else if ($fp->integer)
                return (int)$val;
            else if ($fp->double)
                return (double)$val;
            else if ($fp->boolean)
                return $val == "true";
            else if ($fp->enum)
                return (int)$val;
            else if ($fp->json)
                return json_decode($val);
            else
                return $val;
        }
    }

    // 将数据从参数集写入到模型中的字段
    static function Decode($mdl, $params, $input = true, $output = false)
    {
        $mi = self::Get($mdl);
        if ($mi == null)
            return;
        foreach ($params as $key => $val) {
            $fp = $mi->field($key);
            if (!$fp)
                continue;
            if ($input && !$fp->input)
                continue;
            if ($output && !$fp->output)
                continue;
            $mdl[$key] = self::DecodeValue($fp, $val, $input, $output);
        }
    }

}