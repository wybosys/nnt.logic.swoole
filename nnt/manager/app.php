<?php

namespace Nnt\Manager;

use Nnt\Core\Urls;
use Nnt\Logger\Logger;

define('DIR_NNT', dirname(__DIR__));

class App
{
    function __construct()
    {
        self::$_shared = $this;
    }

    private static $_shared;

    function shared()
    {
        return self::$_shared;
    }

    /**
     * @var object 当前配置
     */
    static $CurrentConfig;

    /**
     * @return
     */
    static function LoadConfig(string $appcfg = "~/app.json", string $devcfg = "~/devops.json")
    {
        $appcfg = Urls::Expand($appcfg);
        if ($devcfg)
            $devcfg = Urls::Expand($devcfg);

        $argv = $_SERVER['argv'];
        if (Config::$DEBUG = in_array("--debug", $argv))
            Logger::Log("debug模式启动");
        else if (Config::$DEVELOP = in_array("--develop", $argv))
            Logger::Log("develop模式启动");
        else if (Config::$PUBLISH = in_array("--publish", $argv))
            Logger::Log("publish模式启动");
        if (Config::$DISTRIBUTION = !Config::IsDebug())
            Logger::Log("distribution模式启动");
        if (Config::$LOCAL = Config::IsLocal())
            Logger::Log("LOCAL 环境");
        if (Config::$DEVOPS = Config::IsDevops())
            Logger::Log("DEVOPS 环境");
        if (Config::$DEVOPS_DEVELOP = Config::IsDevopsDevelop())
            Logger::Log("DEVOPS DEVELOP 环境");
        if (Config::$DEVOPS_RELEASE = Config::IsDevopsRelease())
            Logger::Log("DEVOPS RELEASE 环境");

        // 读取配置
        $cfg = json_decode(file_get_contents($appcfg));
        self::$CurrentConfig = $cfg;

        // 读取系统配置
        $c = $cfg->config;
        if (isset($c->sidexpire))
            Config::$SID_EXPIRE = $c->sidexpire;
        if (isset($c->cidexpire))
            Config::$CID_EXPIRE = $c->cidexpire;
        if (isset($c->cache))
            Config::$CACHE = Urls::Expand($c->cache);

        // 读取开发配置
        // 读取devops的配置
        if ($devcfg) {
            $cfg2 = json_decode(file_get_contents($devcfg));
            if (isset($cfg2->client))
                Config::$CLIENT_ALLOW = $cfg2->client;
            if (isset($cfg2->server))
                Config::$SERVER_ALLOW = $cfg2->server;
            if (isset($cfg2->allow))
                Config::$ACCESS_ALLOW = $cfg2->allow;
            if (isset($cfg2->deny))
                Config::$ACCESS_DENY = $cfg2->deny;
        }

        // 缓存目录
        if (!is_dir(Config::$CACHE))
            mkdir(Config::$CACHE);

        return self::$CurrentConfig;
    }

    function start()
    {
        $cfg = App::$CurrentConfig;
        if (isset($cfg->logger))
            Loggers::Start($cfg->logger);
        //if (isset($cfg->dbms))
        //    Dbmss::Start($cfg->dbms);
        if (isset($cfg->server))
            Servers::Start($cfg->server);
    }

}
