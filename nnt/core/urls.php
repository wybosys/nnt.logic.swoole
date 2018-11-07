<?php

namespace Nnt\Core;

use Nnt\Logger\Logger;

class Urls
{
    static $ROOT;
    static $HOME;

    private static $schemes = [];

    static function RegisterScheme(string $scheme, $proc)
    {
        self::$schemes[$scheme] = $proc;
    }

    // 展开url
    // 如果包含 :// 则拆分成 scheme 和 body，再根绝 scheme 注册的转换器转换
    // 否则按照 / 来打断各个部分，再处理 ~、/ 的设置
    static function Expand(string $url)
    {
        if (strpos($url, "://") !== false) {
            $ps = explode("://", $url);
            if (!isset(self::$schemes[$ps[0]])) {
                Logger::Fatal("没有注册该类型 $ps[0] 的处理器");
                return null;
            }
            $proc = self::$schemes[$ps[0]];
            return $proc($ps[1]);
        }

        $ps = explode('/', $url);
        if ($ps[0] == "~")
            $ps[0] = self::$HOME;
        else if ($ps[0] == "")
            $ps[0] = self::$ROOT;
        else {
            return $url;
        }

        return implode('/', $ps);
    }

}

// 注册普通的url请求
Urls::RegisterScheme("http", function ($body) {
    return $body;
});

Urls::RegisterScheme("https", function ($body) {
    return $body;
});

Urls::$ROOT = '/';
Urls::$HOME = getcwd();
