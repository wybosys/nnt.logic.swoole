<?php

namespace Nnt\Manager;

use Nnt\Core\ClassT;

class Dbmss
{
    private static $_dbs = [];

    static function Start($cfg)
    {
        if (count($cfg)) {
            foreach ($cfg as $node) {
                $srv = ClassT::Instance(ClassT::Entry2Class($node->entry));
                if (!$srv) {
                    echo "无法实例化 $node->entry";
                    return;
                }

                if ($srv->config($node)) {
                    self::$_dbs[$node->id] = $srv;
                    $srv->open();
                } else {
                    echo "$node->id 配置失败";
                }
            }
        }
    }
}