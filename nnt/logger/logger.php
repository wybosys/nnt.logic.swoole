<?php

namespace Nnt\Logger;

class Logger
{
    // 全都是匿名函数
    static public $log;

    static public function Log(string $msg)
    {
        return self::$log($msg);
    }

    static public $warn;

    static public function Warn(string $msg)
    {
        return self::$warn($msg);
    }

    static public $info;

    static public function Info(string $msg)
    {
        return self::$info($msg);
    }

    static public $fatal;

    static public function Fatal(string $msg)
    {
        return self::$fatal($msg);
    }

    static public $exception;

    static public function Exception(\Throwable $err)
    {
        return self::$exception($err);
    }

    static public $error;

    static public function Error(\ErrorException $err)
    {
        return self::$error($err);
    }

    static public function Assert($v, string $msg)
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