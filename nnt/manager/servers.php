<?php

namespace Nnt\Manager;

use Nnt\Core\ClassT;

class Servers
{
    private static $_servers = [];

    static function Start($cfg)
    {
        if (count($cfg)) {
            foreach ($cfg as $node) {
                if (!\Nnt\Config\Config::NodeIsEnable($node))
                    continue;

                $srv = ClassT::Instance(ClassT::Entry2Class($node->entry));
                if (!$srv) {
                    echo "无法实例化 $node->entry";
                    return;
                }

                if ($srv->config($node)) {
                    self::$_servers[$node->id] = $srv;
                    $srv->start();
                } else {
                    echo "$node->id 配置失败";
                }
            }
        }
    }

    static function Find($srvid)
    {
        return self::$_servers[$srvid];
    }
}
