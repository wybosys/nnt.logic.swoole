<?php

namespace Nnt\Config;

use Nnt\Core\ArrayT;

class Config
{
    static function NodeIsEnable($node)
    {
        if (!isset($node->enable))
            return true;
        $conds = explode(",", $node->enable);
        $fnd = ArrayT::QueryObject($conds, function ($e) {
            if ($e === null)
                return false;

            // 仅--debug打开
            if ($e == "debug")
                return \Nnt\Manager\Config::$DEBUG;
            // 仅--develop打开
            if ($e == "develop")
                return \Nnt\Manager\Config::$DEVELOP;
            // 仅--publish打开
            if ($e == "publish")
                return \Nnt\Manager\Config::$PUBLISH;
            // 仅--distribution打开
            if ($e == "distribution")
                return \Nnt\Manager\Config::$DISTRIBUTION;
            // 处于publish或distribution打开
            if ($e == "release")
                return \Nnt\Manager\Config::$PUBLISH || \Nnt\Manager\Config::$DISTRIBUTION;
            // 运行在devops容器中
            if ($e == "devops")
                return \Nnt\Manager\Config::$DEVOPS;
            // 容器内网测试版
            if ($e == "devops-develop")
                return \Nnt\Manager\Config::$DEVOPS_DEVELOP;
            // 容器发布版本
            if ($e == "devops-release")
                return \Nnt\Manager\Config::$DEVOPS_RELEASE;
            // 本地运行
            if ($e == "local")
                return \Nnt\Manager\Config::$LOCAL;

            $argv = $_SERVER['argv'];
            if (in_array('--' . $e, $argv))
                return true;

            return false;
        });

        // 找到一个满足的即为满足
        return $fnd != null;
    }
}