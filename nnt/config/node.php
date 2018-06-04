<?php

namespace Nnt\Config;

class Node
{
    /**
     * @var string 节点id
     */
    public $id;

    /**
     * @var string 实体对应的类
     */
    public $entry;

    /**
     * @var string 开发模式，如果不配置，则代表任何模式都启用，否则只有命中的模式才启用
     */
    public $enable;
}
