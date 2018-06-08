<?php

namespace {
    include_once DIR_NNT . '/core/proto_global.php';
    include_once __DIR__ . '/proto_global.php';
}

namespace Nnt\Store {

    use Nnt\Core\ObjectT;

    class TableSetting
    {
        //生命期
        public $ttl;
    }

    class FieldSetting
    {
        //生命期
        public $ttl;
    }

    class TableInfo
    {
        // 数据库连接名
        public $id;

        // 数据表名
        public $table;

        /**
         * 设置
         * @var TableSetting
         */
        public $setting;

        /**
         * @var array FieldOption
         */
        public $fields;
    }

    class FieldOption
    {
        public $name;

        public $string;
        public $integer;
        public $double;
        public $boolean;
        public $json;
        public $array;
        public $map;

        public $keytype;
        public $valtype;

        public $key; // 主键
        public $subkey; // 次键
        public $notnull; // 不能为空
        public $autoinc; // 自增
        public $unique; // 不重复
        public $unsign;  // 无符号
        public $zero; // 初始化为0
        public $desc; // 递减
        public $loose; // 宽松模式

        /**
         * 字段设置
         * @var FieldSetting
         */
        public $setting;
    }

    // 定义一个数据表模型，注意：数据表模型不能继承
    function table(string $dbid, string $tbnm, TableSetting $set = null): TableInfo
    {
        $ret = new TableInfo();
        $ret->id = $dbid;
        $ret->table = $tbnm;
        $ret->setting = $set;
        return $ret;
    }

    // 返回基础的定义结构，之后的都直接使用固定的类型函数来声明
    function column(array $opts = null, FieldSetting $set = null): FieldOption
    {
        $fp = new FieldOption();
        if ($opts) {
            $fp->key = in_array(key, $opts);
            $fp->subkey = in_array(subkey, $opts);
            $fp->notnull = in_array(notnull, $opts);
            $fp->autoinc = in_array(autoinc, $opts);
            $fp->unique = in_array(unique, $opts);
            $fp->unsign = in_array(unsign, $opts);
            $fp->zero = in_array(zero, $opts);
            $fp->desc = in_array(desc, $opts);
            $fp->loose = in_array(loose, $opts);
        }
        $fp->setting = $set;
        return $fp;
    }

    function colstring($opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->string = true;
        return $fp;
    }

    function colboolean($opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->boolean = true;
        return $fp;
    }

    function colinteger($opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->integer = true;
        return $fp;
    }

    function coldouble($opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->double = true;
        return $fp;
    }

    function colarray($clz, $opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->array = true;
        $fp->valtype = $clz;
        return $fp;
    }

    function colmap($keytyp, $valtyp, $opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->map = true;
        $fp->keytype = $keytyp;
        $fp->valtype = $valtyp;
        return $fp;
    }

    function coljson($opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->json = true;
        return $fp;
    }

    function coltype($clz, $opts = null, $set = null)
    {
        $fp = column($opts, $set);
        $fp->valtype = $clz;
        return $fp;
    }

    class Proto
    {
        private static $_clazzes = [];

        /**
         * 解析模型的定义信息
         * @param $obj
         * @return TableInfo 模型信息
         */
        static function Get($obj): TableInfo
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

        static function ParseClass($clazz): TableInfo
        {
            $reflect = new \ReflectionClass($clazz);
            // 提取model的信息
            $plain = $reflect->getDocComment();
            if (!preg_match('/@table\((\[.*\])?[, ]*(.*)\)/', $plain, $matches))
                return null;

            $ret = null;
            if (!$matches[1])
                $matches[1] = '[]';
            eval("\$ret = call_user_func('\Nnt\Store\table', $matches[1], '$matches[2]');");

            $props = $reflect->getProperties(\ReflectionProperty::IS_PUBLIC);
            foreach ($props as $pinfo) {
                $plain = $pinfo->getDocComment();
                // 给所有类加上‘’，然后再调用函数
                if (!preg_match('/@([a-zA-Z]+)\((.+)\)/', $plain, $matches))
                    continue;
                $func = '\Nnt\Store\\' . $matches[1] . "_";
                $args = preg_replace('/((?:\\\\[a-zA-Z]+)+)/', "'$0'", $matches[2]);

                $fi = null;
                eval("\$fi = call_user_func('$func', $args);");
                $fi->name = $pinfo->name;
                $ret->fields[$fi->name] = $fi;
            }

            return $ret;
        }

        const POD_TYPES = ['string', 'integer', 'double', 'boolean'];

        // 填数据库对象
        function Decode($mdl, $params)
        {
            $ti = self::Get($mdl);
            if ($ti == null)
                return;
            foreach ($params as $key => $val) {
                $fp = @$ti->fields[$key];
                if (!$fp)
                    continue;
                if ($val == null) {
                    if (!$fp->loose)
                        $mdl->{$key} = null; // 从数据库读取数据时采用严格模式：字段如果在数据库中为null，则拿出来后也是null
                    continue;
                }
                if ($fp->valtype) {
                    if ($fp->array) {
                        if (in_array($fp->valtype, self::POD_TYPES)) {
                            $mdl->{$key} = $val;
                        } else {
                            $clz = $fp->valtype;
                            if ($clz == 'object') {
                                // object类似于json，不指定数据类型
                                $mdl->{$key} = $val;
                            } else {
                                $arr = [];
                                foreach ($val as $e) {
                                    $t = new $clz();
                                    self::Decode($t, $e);
                                    $arr[] = $t;
                                }
                                $mdl->{$key} = $arr;
                            }
                        }
                    } else if ($fp->map) {
                        $map = [];
                        if (in_array($fp->valtype, self::POD_TYPES)) {
                            foreach ($val as $ek => $ev)
                                $map[$ek] = $ev;
                        } else {
                            $clz = $fp->valtype;
                            foreach ($val as $ek => $ev) {
                                $t = new $clz();
                                self::Decode($t, $ev);
                                $map[$ek] = $t;
                            }
                        }
                        $mdl->{$key} = $map;
                    } else {
                        $clz = $fp->valtype;
                        if ($clz == "object") {
                            $mdl->{$key} = $val;
                        } else if (is_object($val)) {
                            $t = new $clz();
                            self::Decode($t, $val);
                            $mdl->{$key} = $t;
                        } else if (!$fp->loose) {
                            $mdl->{$key} = null;
                        }
                    }
                } else {
                    $mdl->{$key} = $val;
                }
            }
        }

        function Output($mdl, $def = [])
        {
            if (!$mdl)
                return $def;
            $ti = self::Get($mdl);
            if (!$ti)
                return $def;
            $r = [];
            foreach ($ti->fields as $fk => $fp) {
                if (!isset($mdl->{$fk}))
                    continue;
                $val = $mdl->{$fk};
                if ($fp->valtype) {
                    if ($fp->array) {
                        if (in_array(string, self::POD_TYPES)) {
                            $r[$fk] = $val;
                        } else {
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
                                    $m[$k] = $v; // 不需要转换key的类型，val为真实的Map对象
                                }
                            } else {
                                foreach ($val as $k => $v) {
                                    $m[$k] = self::Output($v);
                                }
                            }
                        }
                        $r[$fk] = $m;
                    } else {
                        $v = self::Output($val, null);
                        if ($v == null)
                            $v = ObjectT::ToObject($val);
                        $r[$fk] = $v;
                    }
                } else {
                    $r[$fk] = $val;
                }
            }
            return $r;
        }

    }
}

