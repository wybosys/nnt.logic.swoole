<?php

namespace Nnt\Core;

include_once __DIR__ . "/proto_global.php";

use Nnt\Logger\Logger;

/**
 * Class FieldInfo 模型中字段的定义
 */
class FieldInfo
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var boolean
     */
    public $string;

    /**
     * @var boolean
     */
    public $integer;

    /**
     * @var boolean
     */
    public $double;

    /**
     * @var boolean
     */
    public $boolean;

    /**
     * @var boolean
     */
    public $file;

    /**
     * @var boolean
     */
    public $enum;

    /**
     * @var boolean
     */
    public $array;

    /**
     * @var boolean
     */
    public $map;

    /**
     * @var boolean
     */
    public $multimap;

    /**
     * @var boolean
     */
    public $object;

    /**
     * @var boolean
     */
    public $json;

    /**
     * @var string
     */
    public $valtype;

    /**
     * @var string
     */
    public $keytype;

    /**
     * @var boolean
     */
    public $optional;

    /**
     * @var integer
     */
    public $index;

    /**
     * @var boolean
     */
    public $input;

    /**
     * @var boolean
     */
    public $output;

    /**
     * @var string
     */
    public $comment;
}

/**
 * Class ModelInfo 模型的原型信息
 */
class ModelInfo
{
    /**
     * 需要登陆验证
     * @var boolean
     */
    public $auth;

    /**
     * 是否是枚举类型，因为语言限制，无法对enum对象添加decorate处理，只能在服务器端使用class来模拟
     * @var boolean
     */
    public $enum;

    /**
     * 用来定义常量，或者模拟str的枚举
     * @var boolean
     */
    public $const;

    /**
     * 隐藏后就不会加入到models列表中
     * @var boolean
     */
    public $hidden;

    /**
     * 父类，目前用来生成api里面的父类名称
     * @var boolean
     */
    public $super;

    /**
     * 所有的数据项
     * @var array FieldInfo
     * Map<string, FieldInfo>
     */
    public $fields = [];

    /**
     * @return FieldInfo
     */
    function field($nm)
    {
        return @$this->fields[$nm];
    }
}

function model($options = [], $super = null): ModelInfo
{
    $ret = new ModelInfo();
    $ret->auth = in_array('auth', $options);
    $ret->enum = in_array('enumm', $options);
    $ret->const = in_array('const', $options) || in_array('constant', $options);
    $ret->hidden = in_array('hidden', $options);
    $ret->super = $super;
    return $ret;
}

function field_($id, $opts = [], $comment = null): FieldInfo
{
    $ret = new FieldInfo();
    $ret->index = $id;
    $ret->input = in_array('input', $opts);
    $ret->output = in_array('output', $opts);
    $ret->optional = in_array('optional', $opts);
    $ret->comment = $comment;
    return $ret;
}

function string_($id, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->string = true;
    return $ret;
}

function boolean_($id, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->boolean = true;
    return $ret;
}

function integer_($id, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->integer = true;
    return $ret;
}

function double_($id, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->double = true;
    return $ret;
}

function array_($id, $clz, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->array = true;
    $ret->valtype = $clz;
    return $ret;
}

function map_($id, $keytyp, $valtyp, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->map = true;
    $ret->keytype = $keytyp;
    $ret->valtype = $valtyp;
    return $ret;
}

function json_($id, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->json = true;
    return $ret;
}

function type_($id, $clz, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->valtype = $clz;
    return $ret;
}

function enumerate_($id, $clz, $opts = [], $comment = null): FieldInfo
{
    $ret = field_($id, $opts, $comment);
    $ret->enum = true;
    $ret->valtype = $clz;
    return $ret;
}

function file_($id, $opts = [], $comment = null): FieldInfo
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
        if (is_array($obj))
            return null;
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
        foreach ($props as $pinfo) {
            // 过滤掉继承的属性
            if ($pinfo->getDeclaringClass() != $reflect)
                continue;

            $plain = $pinfo->getDocComment();

            // 给所有类加上‘’，然后再调用函数
            if (!preg_match('/@([a-zA-Z]+)\((.+)\)/', $plain, $matches))
                continue;
            $func = '\Nnt\Core\\' . $matches[1] . "_";
            $args = preg_replace('/((?:\\\\[a-zA-Z]+)+)/', "'$0'", $matches[2]);

            $fi = null;
            eval("\$fi = call_user_func('$func', $args);");
            $fi->name = $pinfo->name;
            $ret->fields[$fi->name] = $fi;
        }

        return $ret;
    }

    static function Output($mdl)
    {
        if (!$mdl)
            return null;
        $mi = self::Get($mdl);
        if (!$mi)
            return null;
        $r = [];
        foreach ($mi->fields as $fk => $fp) {
            if (!$fp->output || !isset($mdl->{$fk})) // 不能和客户端一样删除掉对fk的判断，服务端会使用model直接扔到数据库中查询，去掉后会生成初始值查询字段
                continue;
            $val = $mdl->{$fk};
            if ($fp->valtype) {
                if ($fp->array) {
                    // 通用类型，则直接可以输出
                    if (in_array($fp->valtype, self::POD_TYPES)) {
                        $arr = [];
                        switch ($fp->valtype) {
                            case string:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = $e ? (string)$e : null;
                                    }
                                }
                                break;
                            case integer:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = $e ? (int)$e : null;
                                    }
                                }
                                break;
                            case double:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = $e ? (double)$e : null;
                                    }
                                }
                                break;
                            case boolean:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = !!$e;
                                    }
                                }
                                break;
                            case json:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = $e;
                                    }
                                }
                                break;
                        }
                        $r[$fk] = $arr;
                    } else {
                        // 特殊类型，需要迭代进去
                        $arr = [];
                        foreach ($val as $e) {
                            $arr[] = self::Output($e);
                        }
                        $r[$fk] = $arr;
                    }
                } else if ($fp->map) {
                    $m = [];
                    if ($val) {
                        if (in_array($fp->valtype, self::POD_TYPES)) {
                            foreach ($val as $k => $v) {
                                $m[$k] = $v;
                            }
                        } else {
                            foreach ($val as $k => $v) {
                                $m[$k] = self::Output($v);
                            }
                        }
                    }
                    $r[$fk] = $m;
                } else if ($fp->enum) {
                    $r[$fk] = (int)$val;
                } else {
                    $v = self::Output($val);
                    if ($v == null)
                        $v = ObjectT::ToObject($val);
                    $r[$fk] = $v;
                }
            } else {
                if ($fp->string)
                    $r[$fk] = (string)$val;
                else if ($fp->integer)
                    $r[$fk] = (int)$val;
                else if ($fp->double)
                    $r[$fk] = (double)$val;
                else if ($fp->boolean)
                    $r[$fk] = !!$val;
                else if ($fp->enum)
                    $r[$fk] = (int)$val;
                else if ($fp->json)
                    $r[$fk] = $val;
                else {
                    $v = self::Output($val);
                    if ($v == null)
                        $v = ObjectT::ToObject($val);
                    $r[$fk] = $v;
                }
            }
        }
        // 输出内置的数据
        if (isset($mdl->_mid))
            $r["_mid"] = $mdl->_mid;
        return $r;
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

    const POD_TYPES = [string, integer, double, boolean, json];

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
                        switch ($fp->valtype) {
                            case string:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = $e ? (string)$e : null;
                                    }
                                }
                                break;
                            case integer:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = (int)$e;
                                    }
                                }
                                break;
                            case double:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = (double)$e;
                                    }
                                }
                                break;
                            case boolean:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = !!$e;
                                    }
                                }
                                break;
                            case json:
                                {
                                    foreach ($val as $e) {
                                        $arr[] = json_decode($e);
                                    }
                                }
                                break;
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
                    switch ($fp->valtype) {
                        case string:
                            {
                                foreach ($val as $ek => $ev) {
                                    $map[$ek] = $ev ? (string)$ev : null;
                                }
                            }
                            break;
                        case integer:
                            {
                                foreach ($val as $ek => $ev) {
                                    $map[$ek] = $ev ? (int)$ev : null;
                                }
                            }
                            break;
                        case double:
                            {
                                foreach ($val as $ek => $ev) {
                                    $map[$ek] = $ev ? (double)$ev : null;
                                }
                            }
                            break;
                        case boolean:
                            {
                                foreach ($val as $ek => $ev) {
                                    $map[$ek] = !!$ev;
                                }
                            }
                            break;
                        case json:
                            {
                                foreach ($val as $ek => $ev) {
                                    $map[$ek] = json_decode($ev);
                                }
                            }
                            break;
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
            $mdl->{$key} = self::DecodeValue($fp, $val, $input, $output);
        }
    }

    // 类名为定义的最后一段
    static function GetClassName($clazz): string
    {
        $cmps = explode('\\', $clazz);
        return $cmps[count($cmps) - 1];
    }

    /**
     * @return int[]
     * Map<string, int>
     */
    static function ConstsOfClass($clazz)
    {
        $ret = [];
        $reflect = new \ReflectionClass($clazz);
        foreach ($reflect->getConstants() as $name => $val) {
            $ret[$name] = $val;
        }
        return $ret;
    }

    static function FpToTypeDef(FieldInfo $fp): string
    {
        if ($fp->string) {
            $typ = "string";
        } else if ($fp->integer) {
            $typ = "number";
        } else if ($fp->double) {
            $typ = "number";
        } else if ($fp->boolean) {
            $typ = "boolean";
        } else if ($fp->array) {
            $typ = "Array<";
            switch ($fp->valtype) {
                case "string":
                    $vt = "string";
                    break;
                case "double":
                case "integer":
                    $vt = "number";
                    break;
                case "boolean":
                    $vt = "boolean";
                    break;
                default:
                    $vt = $fp->valtype;
                    break;
            }
            $typ .= $vt;
            $typ .= ">";
        } else if ($fp->map) {
            $typ = "Map<" . self::ValtypeDefToDef($fp->keytype) . ", " . self::ValtypeDefToDef($fp->valtype) . ">";
        } else if ($fp->multimap) {
            $typ = "Multimap<" . self::ValtypeDefToDef($fp->keytype) . ", " . self::ValtypeDefToDef($fp->valtype) . ">";
        } else if ($fp->enum) {
            $typ = self::GetClassName($fp->valtype);
        } else if ($fp->file) {
            if ($fp->input)
                $typ = "any";
            else
                $typ = "string";
        } else if ($fp->json || $fp->object) {
            $typ = "Object";
        } else {
            $typ = $fp->valtype;
        }
        return $typ;
    }

    static function ValtypeDefToDef($def): string
    {
        switch ($def) {
            case "string":
                return "string";
            case "double":
            case "integer":
                return "number";
            case "boolean":
                return "boolean";
        }

        return self::GetClassName($def);
    }

    static function FpToDecoDef(FieldInfo $fp, $ns = ""): string
    {
        $deco = null;
        if ($fp->string)
            $deco = "@" . $ns . "string(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->integer)
            $deco = "@" . $ns . "integer(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->double)
            $deco = "@" . $ns . "double(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->boolean)
            $deco = "@" . $ns . "boolean(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        else if ($fp->array) {
            $deco = "@" . $ns . "array(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->map) {
            $deco = "@" . $ns . "map(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->multimap) {
            $deco = "@" . $ns . "multimap(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->enum) {
            $deco = "@" . $ns . "enumerate(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->file) {
            $deco = "@" . $ns . "file(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->json) {
            $deco = "@" . $ns . "json(" . $fp->index . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        } else {
            $deco = "@" . $ns . "type(" . $fp->index . ", " . self::FpToValtypeDef($fp, $ns) . ", " . self::FpToOptionsDef($fp, $ns) . self::FpToCommentDef($fp) . ")";
        }
        return $deco;
    }

    static function FpToDecoDefPHP(FieldInfo $fp): string
    {
        $deco = null;
        if ($fp->string) {
            $deco = "@Api(" . $fp->index . ", [string], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var string";
        } else if ($fp->integer) {
            $deco = "@Api(" . $fp->index . ", [integer], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var int";
        } else if ($fp->double) {
            $deco = "@Api(" . $fp->index . ", [double], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var double";
        } else if ($fp->boolean) {
            $deco = "@Api(" . $fp->index . ", [boolean], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
            $deco .= "\n\t* @var boolean";
        } else if ($fp->array) {
            $deco = "@Api(" . $fp->index . ", [array, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->map) {
            $deco = "@Api(" . $fp->index . ", [map, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->multimap) {
            $deco = "@Api(" . $fp->index . ", [multimap, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->enum) {
            $deco = "@Api(" . $fp->index . ", [enum, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->file) {
            $deco = "@Api(" . $fp->index . ", [file], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else if ($fp->json) {
            $deco = "@Api(" . $fp->index . ", [json], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        } else {
            $deco = "@Api(" . $fp->index . ", [type, " . self::FpToValtypeDef($fp) . "], " . self::FpToOptionsDef($fp) . self::FpToCommentDef($fp) . ")";
        }
        return $deco;
    }

    static function FpToCommentDef(FieldInfo $fp): string
    {
        return $fp->comment ? (', "' . $fp->comment . '"') : "";
    }

    static function FpToOptionsDef(FieldInfo $fp, $ns = ""): string
    {
        $r = [];
        if ($fp->input)
            $r[] = $ns . 'input';
        if ($fp->output)
            $r[] = $ns . 'output';
        if ($fp->optional)
            $r[] = $ns . 'optional';
        return "[" . implode(', ', $r) . "]";
    }

    static function FpToValtypeDef(FieldInfo $fp, $ns = ""): string
    {
        $t = [];
        if ($fp->keytype) {
            $t[] = self::ValtypeDefToDefType($fp->keytype, $ns);
        }
        if ($fp->valtype) {
            $t[] = self::ValtypeDefToDefType($fp->valtype, $ns);
        }
        return implode(', ', $t);
    }

    static function ValtypeDefToDefType($def, $ns = ''): string
    {
        switch ($def) {
            case "string":
                return $ns . "string_t";
            case "double":
            case "integer":
                return $ns . "number_t";
            case "boolean":
                return $ns . "boolean_t";
            case "object":
                return "Object";
        }

        return self::GetClassName($def);
    }
}
