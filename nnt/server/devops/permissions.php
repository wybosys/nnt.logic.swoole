<?php

namespace Nnt\Server\Devops;

use Nnt\Core\STATUS;
use Nnt\Manager\Config;

class Permissions
{
    const KEY_PERMISSIONID = "_permissionid";
    const KEY_SKIPPERMISSION = "_skippermission";
    const REDIS_PERMISSIONIDS = 17;

    // 保存上一次读取的permission数据
    static $CONFIG_HASH = null;
    static $PID = null;

    static function PID(): string
    {
        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file)) {
            throw new \Exception("没有找到文件 $file", STATUS::PERMISSIO_FAILED);
        }

        // 从apcu中读取缓存的pid
        if (self::$CONFIG_HASH) {
            $ftime = filemtime($file);
            if (self::$CONFIG_HASH != $ftime) {
                $cfg = json_decode(file_get_contents($file));
                self::$PID = $cfg->id;
                self::$CONFIG_HASH = $ftime;
                return self::$PID;
            } else {
                return self::$PID;
            }
        }

        $ftime = filemtime($file);
        $cfg = json_decode(file_get_contents($file));
        self::$PID = $cfg->id;
        self::$CONFIG_HASH = $ftime;

        return self::$PID;
    }

    static function IsEnabled(): bool
    {
        // 只有devops环境下才具备检测权限的环境
        return Config::IsDevops();
    }

    static $DEVOPS_CONFIG = null;

    static function DevopsConfig()
    {
        if (self::$DEVOPS_CONFIG == null) {
            $cfgph = APP_DIR . '/devops.json';
            self::$DEVOPS_CONFIG = json_decode(file_get_contents($cfgph));
        }
        return self::$DEVOPS_CONFIG;
    }
}
