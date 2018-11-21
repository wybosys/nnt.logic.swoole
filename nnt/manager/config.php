<?php

namespace Nnt\Manager;

class Config
{
    // DEBUG模式
    static $DEBUG = false;

    // DEVELOP模式，和debug的区别，develop用来部署开发服务器，debug用来做本地开发，会影响到app.json中对服务器的启动处理
    static $DEVELOP = false;

    // PUBLISH模式，和release类似，除了会使用线上配置外，其他又和develop一致
    static $PUBLISH = false;

    // 正式版模式
    static $DISTRIBUTION = true;

    // 本地模式
    static $LOCAL = false;

    // 容器部署
    static $DEVOPS = false;

    // 内网测试容器部署
    static $DEVOPS_DEVELOP = false;

    // 外网容器部署
    static $DEVOPS_RELEASE = true;

    // sid过期时间，此框架中时间最小单位为秒
    static $SID_EXPIRE = 86400;

    // clientid 过期时间
    static $CID_EXPIRE = 600;

    // model含有最大fields的个数
    static $MODEL_FIELDS_MAX = 100;

    // transaction超时时间
    static $TRANSACTION_TIMEOUT = 20;

    // 是否允许客户端访问
    static $CLIENT_ALLOW = false;

    // 是否允许服务端访问
    static $SERVER_ALLOW = true;

    // 白名单
    static $ACCESS_ALLOW = [];

    // 黑名单
    static $ACCESS_DENY = [];

    // 服务端缓存目录
    static $CACHE = "cache";

    // 最大下载文件的大小
    static $FILESIZE_LIMIT = 10485760; // 10M

    // 判断是否是开发版
    static function IsDebug()
    {
        return Config::$DEBUG || Config::$DEVELOP || Config::$PUBLISH;
    }

    // 是否是正式版
    static function IsRelease()
    {
        return Config::$DISTRIBUTION;
    }

    static function DebugValue($d, $r)
    {
        return Config::$DISTRIBUTION ? $r : $d;
    }

    // 支持DEVOPS的架构判断
    private static $_ISDEVOPS;
    private static $_ISDEVOPSDEVELOP;
    private static $_ISDEVOPSRELEASE;
    private static $_ISLOCAL;

    static function IsLocal()
    {
        return getenv('DEVOPS') == null;
    }

    static function IsDevops()
    {
        return getenv('DEVOPS') != null;
    }

    static function IsDevopsDevelop()
    {
        return getenv('DEVOPS') != null && getenv('DEVOPS_RELEASE') == null;
    }

    static function IsDevopsRelease()
    {
        return getenv('DEVOPS_RELEASE') != null;
    }
}

// 定义全局的应用路径
define('APP_DIR', dirname(dirname(__DIR__)));
