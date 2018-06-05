<?php

namespace Nnt\Manager;

use Nnt\Core\ArrayT;
use Nnt\Core\ClassT;

class Servers
{
    private static $_servers = [];

    static function Start($cfg)
    {
        if (count($cfg)) {
            ArrayT::ForeachSync($cfg, function ($node, $idx, $next) {
                if (!\Nnt\Config\Config::NodeIsEnable($node))
                    return;

                $srv = ClassT::Instance(ClassT::Entry2Class($node->entry));
                if (!$srv) {
                    echo "无法实例化 $node->entry";
                    return;
                }

                if ($srv->config($node)) {
                    self::$_servers[$node->id] = $srv;
                    $srv->start($next);
                } else {
                    echo "$node->id 配置失败";
                }
            });
        }
    }
}