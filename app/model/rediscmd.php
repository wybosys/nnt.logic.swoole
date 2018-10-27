<?php

namespace App\Model;

/**
 * @model()
 */
class RedisCmd
{
    /**
     * @string(1, [input], "key")
     */
    public $key;

    /**
     * @string(2, [input, output, optional], "value不输入则为读取")
     */
    public $value;
}
