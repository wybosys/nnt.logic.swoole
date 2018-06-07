<?php

namespace Nnt\Component;

const RE_PARAMETER = '/\{\{([a-zA-Z0-9_.]+)\}\}/';

class Template
{
    /**
     * @var string
     */
    private $_buf;

    /**
     * @return Template
     */
    function compile(string $str, string $pat = RE_PARAMETER)
    {
        // 标记str中的变量，避免循环填数据
        $this->_buf = preg_replace($pat, "@@__$1", $str);
        return $this;
    }

    function render(array $parameters): string
    {
        $str = $this->_buf;
        foreach ($parameters as $k => $e) {
            $str = str_replace("@@__" + k, $e, $str);
        }
        return $str;
    }

}