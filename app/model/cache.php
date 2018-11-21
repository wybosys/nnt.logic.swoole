<?php

namespace App\Model;

/**
 * @model()
 */
class Cache
{
    /**
     * @string(1, [input], "输入")
     */
    public $key;

    /**
     * @string(2, [input, output, optional], "输出")
     */
    public $value;
}


