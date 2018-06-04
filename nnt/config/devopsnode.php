<?php

namespace Nnt\Config;

class DevopsNode
{
    /**
     * @var bool 是否允许客户端访问本服务
     */
    public $client;

    /**
     * @var bool 是否允许服务端访问本服务
     */
    public $server;

    /**
     * @var string[] 白名单
     */
    public $allow;

    /**
     * @var string[] 黑名单
     */
    public $deny;
}
