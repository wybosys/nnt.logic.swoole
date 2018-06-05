<?php

namespace Nnt\Logger;

class Logger
{
    // 全都是匿名函数
    static $log;

    static function Log(string $msg)
    {
        return (self::$log)($msg);
    }

    static $warn;

    static function Warn(string $msg)
    {
        return (self::$warn)($msg);
    }

    static $info;

    static function Info(string $msg)
    {
        return (self::$info)($msg);
    }

    static $fatal;

    static function Fatal(string $msg)
    {
        return (self::$fatal)($msg);
    }

    static $exception;

    static function Exception(\Throwable $err)
    {
        return (self::$exception)($err);
    }

    static $error;

    static function Error(\ErrorException $err)
    {
        return (self::$error)($err);
    }

    static function Assert($v, string $msg)
    {
        if (!$v) {
            echo $msg;
        }
    }
}

Logger::$log = function (string $msg) {
    echo $msg;
};

Logger::$warn = function (string $msg) {
    echo $msg;
};

Logger::$info = function (string $msg) {
    echo $msg;
};

Logger::$fatal = function (string $msg) {
    echo $msg;
};

Logger::$exception = function (\Throwable $err) {
    echo $err->getMessage();
};

Logger::$error = function (\ErrorException $err) {
    echo $err->getMessage();
};