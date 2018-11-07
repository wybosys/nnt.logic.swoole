<?php

namespace Nnt\Config;

class AppNodes
{
    /**
     * @var object 全局配置的节点
     */
    public $config;

    /**
     * @var Node[] 服务节点
     */
    public $server;

    /**
     * @var Node[] 数据库节点
     */
    public $dbms;

    /**
     * @var Node[] 日志节点
     */
    public $logger;
}
