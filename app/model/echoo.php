<?php

namespace App\Model;

/**
 * @model()
 * @table("mysql", "echoo")
 */
class Echoo
{
    /**
     * @string(1, [input], "输入")
     * @colstring()
     */
    public $input;

    /**
     * @string(2, [output], "输出")
     * @colstring()
     */
    public $output;

    /**
     * @integer(3, [output], "服务器时间")
     * @colinteger()
     */
    public $time;

    /**
     * @type(4, \App\Model\Info, [output])
     */
    public $info;
}

