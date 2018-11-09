<?php

namespace Nnt\Server\Devops;

use Nnt\Core\STATUS;
use Nnt\Manager\Config;

class Permissions
{
    const KEY_PERMISSIONID = "_permissionid";
    const KEY_SKIPPERMISSION = "_skippermission";
    const REDIS_PERMISSIONIDS = 17;

    static function PID(): string
    {
        $file = APP_DIR . '/run/permission.cfg';
        if (!file_exists($file)) {
            throw new \Exception("没有找到文件 $file", STATUS::PERMISSIO_FAILED);
        }

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
