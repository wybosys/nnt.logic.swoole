<?php

namespace Nnt\Core;

class VariantType
{
    const UNKNOWN = 0;
    const STRING = 2;
    const OBJECT = 3;
    const BOOLEAN = 4;
    const NUMBER = 5;
}

class Variant implements ISerializableObject
{
    function __construct($o)
    {
        $this->_raw = $o;
        if (!$o)
            return;
        if (is_string($o)) {
            $this->_type = VariantType::STRING;
            $this->_str = $o;
        } else if (is_bool($o)) {
            $this->_type = VariantType::BOOLEAN;
            $this->_bol = $o;
        } else if (is_numeric($o)) {
            $this->_type = VariantType::NUMBER;
            $this->_num = $o;
        } else {
            $this->_type = VariantType::OBJECT;
            $this->_jsobj = $o;
        }
    }

    private $_raw;
    private $_type = VariantType::UNKNOWN;

    private $_str;
    private $_bol;
    private $_num;
    private $_jsobj;

    function object()
    {
        return $this->_jsobj;
    }

    static function FromString(string $str): Variant
    {
        if (!$str)
            return null;
        $t = new Variant(null);
        if (!$t->unserialize($str))
            return null;
        return $t;
    }

    function value()
    {
        if ($this->_type == VariantType::STRING)
            return $this->_str;
        else if ($this->_type == VariantType::OBJECT)
            return $this->_jsobj;
        else if ($this->_type == VariantType::BOOLEAN)
            return $this->_bol;
        else if ($this->_type == VariantType::NUMBER)
            return $this->_num;
        return null;
    }

    function setValue($v)
    {
        if ($this->_type == VariantType::STRING)
            $this->_str = $v;
        else if ($this->_type == VariantType::OBJECT)
            $this->_jsobj = $v;
        else if ($this->_type == VariantType::BOOLEAN)
            $this->_bol = $v;
        else if ($this->_type == VariantType::NUMBER)
            $this->_num = $v;
    }

    function toString(): string
    {
        if ($this->_str)
            return $this->_str;
        if ($this->_type == VariantType::OBJECT)
            $this->_str = json_encode($this->_jsobj);
        else if ($this->_type == VariantType::BOOLEAN)
            $this->_str = $this->_bol ? "true" : "false";
        else if ($this->_type == VariantType::NUMBER)
            $this->_str = (string)$this->_num;
        return $this->_str;
    }

    function toJsObj()
    {
        if ($this->_jsobj)
            return $this->_jsobj;
        $this->_jsobj = json_decode($this->toString());
        return $this->_jsobj;
    }

    function serialize(): string
    {
        $s = ["_t" => $this->_type, "_i" => "vo", "_d" => $this->value()];
        return json_encode($s);
    }

    function unserialize(string $str): bool
    {
        $obj = json_decode($str);
        if (!$obj)
            return false;
        if ($obj->_i != "vo") {
            if (is_numeric($obj)) {
                $this->_type = VariantType::NUMBER;
                $this->_num = $obj;
                return true;
            } else if (is_string($obj)) {
                $this->_type = VariantType::STRING;
                $this->_str = $obj;
                return true;
            } else if (is_bool($obj)) {
                $this->_type = VariantType::BOOLEAN;
                $this->_bol = $obj;
                return true;
            }
            return false;
        }
        $this->_type = $obj->_t;
        $this->value = $obj->_d;
        return true;
    }

}