<?php

namespace Nnt\Render;

use Nnt\Logger\Logger;
use Nnt\Server\Transaction;
use Nnt\Server\TransactionSubmitOption;

interface IRender
{
    function type(): string;

    function render(Transaction $t, TransactionSubmitOption $opt = null): string;
}

class Render
{
    private static $_renders = [];

    static function Register(string $name, IRender $render)
    {
        if (isset(self::$_renders[$name])) {
            Logger::Fatal("重复注册渲染器 $name");
            return;
        }
        self::$_renders[$name] = $render;
    }

    static function Find($name): IRender
    {
        if (!isset(self::$_renders[$name]))
            $name = "json"; // 默认用json
        return self::$_renders[$name];
    }
}

Render::Register("json", new Json());
Render::Register("raw", new Raw());
