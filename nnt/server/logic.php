<?php

namespace Nnt\Server;

use Nnt\Logger\Logger;

class Logic extends Server
{
    /**
     * @var string 服务器的地址
     */
    public $host;

    function config($cfg): bool
    {
        if (!parent::config($cfg))
            return false;
        if (!isset($cfg->host))
            return false;
        $this->host = self::MapHost($cfg->host);
        return true;
    }

    function start()
    {

    }

    function stop()
    {

    }

    // php使用域名访问时有可能过慢，所以需要转换仅通过host来访问的地址
    // 不能转换通过www.xxx.com这类的地址，避免服务器无法通过server_name重定位服务
    static function MapHost(string $host): string
    {
        $url = parse_url($host);
        if (strpos($url['host'], '.') !== false)
            return $host;
        $ip = gethostbyname($url['host']);
        if (!isset($url['path']))
            $url['path'] = '';
        $new = $url['scheme'] . '://' . $ip . $url['path'];

        Logger::Info("logic的host从" . $host . "自动转换为" . $new);

        return $new;
    }
}
