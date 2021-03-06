<?php

namespace Nnt\Server;

use Nnt\Core\STATUS;
use Nnt\Logger\Logger;
use Nnt\Manager\Config;
use Nnt\Manager\Servers;
use Nnt\Server\Devops\Permissions;

class Service
{
    static function RawCall(string $idr, string $sub, array $args)
    {
        // 从配置中读取基础的host地址
        $logic = Servers::Find($idr);
        if (!$logic) {
            throw new \Exception("没有找到logic的配置", STATUS::TARGET_NOT_FOUND);
        }
        $host = $logic->host;

        // 添加permission的信息
        if (Permissions::IsEnabled()) {
            $args[Permissions::KEY_PERMISSIONID] = Permissions::PID();
        }

        // 添加跳过的标记
        if (!Config::IsDevopsRelease()) {
            $args[Permissions::KEY_SKIPPERMISSION] = 1;
        }

        $url = $host . '/' . $sub . '/?' . http_build_query($args);
        Logger::Info("S2S: $url");

        // 初始化curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Linux) AppleWebKit/600.1.4 (KHTML, like Gecko) NetType/WIFI');

        // 解决curl卡顿的问题
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

        $msg = curl_exec($ch);
        curl_close($ch);
        
        return $msg;
    }

    /**
     * 服务间调用
     * @throws \Exception
     */
    static function Call(string $idr, string $sub, array $args)
    {
        $msg = self::RawCall($idr, $sub, $args);
        $ret = json_decode($msg);
        if (!$ret) {
            throw new \Exception($msg, STATUS::FORMAT_ERROR);
        } else if (!isset($ret->code)) {
            throw new \Exception($msg, STATUS::FORMAT_ERROR);
        } else {
            if (isset($ret->message) && !isset($ret->data))
                $ret->data = $ret->message;
            else if (isset($ret->data) && !isset($ret->message))
                $ret->message = $ret->data;
        }
        return $ret;
    }

    /**
     * 服务间调用
     * @throws \Exception
     */
    static function Fetch(string $idr, string $sub, array $args)
    {
        $ret = self::Call($idr, $sub, $args);
        if ($ret->code != STATUS::OK) {
            throw new \Exception("API调用失败", $ret->code);
        }
        return $ret->data;
    }

    /**
     * 服务间调用
     */
    static function Get(string $idr, string $sub, array $args)
    {
        try {
            $ret = self::Call($idr, $sub, $args);
            if ($ret->code != STATUS::OK) {
                return null;
            }
            return $ret->data;
        } catch (\Exception $err) {
            return null;
        }
    }
}
