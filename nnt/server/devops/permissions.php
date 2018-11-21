<?php

namespace Nnt\Server\Devops;

use Nnt\Core\STATUS;
use Nnt\Core\StringT;
use Nnt\Manager\Config;

class Permissions
{
    const KEY_PERMISSIONID = "_permissionid";
    const KEY_SKIPPERMISSION = "_skippermission";
    const REDIS_PERMISSIONIDS = 17;

    function __construct()
    {
        // 读取devops.json
        $file = APP_DIR . '/devops.json';
        $this->_devops = json_decode(file_get_contents($file));

        // 读取配置的名称
        $this->_domain = StringT::SubStr($this->_devops->path, 16);
    }

    static function PID(): string
    {
        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file)) {
            throw new \Exception("没有找到文件 $file", STATUS::PERMISSIO_FAILED);
        }

        // 使用apcu的ttl定时刷新permission.cfg中更新的当前pid
        if (apcu_exists(self::KEY_PERMISSIONID)) {
            $pid = apcu_fetch(self::KEY_PERMISSIONID);
            return $pid;
        }

        $cfg = json_decode(file_get_contents($file));
        $pid = $cfg->id;
        apcu_store(self::KEY_PERMISSIONID, $pid, 60);

        return $pid;
    }

    static function IsEnabled(): bool
    {
        // 只有devops环境下才具备检测权限的环境
        return Config::IsDevops();
    }

    // 和devops.json的配置保持一致
    private $_devops;

    // devops配置的domain名
    private $_domain;

    // 单件
    static $shared;
}

Permissions::$shared = new Permissions();
