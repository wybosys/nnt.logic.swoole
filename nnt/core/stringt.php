<?php

namespace Nnt\Core;

class StringT
{

    // 去除掉float后面的0
    static function TrimFloat(string $str): string
    {
        $lr = explode('.', $str);
        if (count($lr) != 2) {
            return $str;
        }

        $ro = $lr[1];
        $m = false;
        $rs = '';

        for ($i = count($ro); $i > 0; --$i) {
            $c = $ro[$i - 1];
            if (!$m && $c != '0')
                $m = true;
            if ($m)
                $rs = $c . $rs;
        }
        if (strlen($rs) == 0)
            return $lr[0];
        return $lr[0] . '.' . $rs;
    }

    // 标准的substr只支持正向，这里实现的支持两个方向比如，substr(1, -2)
    static function SubStr(string $str, int $pos, int $len = 0): string
    {
        if ($len >= 0)
            return substr($str, $pos, $len == 0 ? null : $len);
        if ($pos < 0)
            $pos = strlen($str) + $pos;
        $pos += $len;
        $of = 0;
        if ($pos < 0) {
            $of = $pos;
            $pos = 0;
        }
        return substr($str, $pos, -$len + $of);
    }

    static function Repeat(string $str, int $count = 1): string
    {
        $r = "";
        while ($count--) {
            $r .= $str;
        }
        return $r;
    }
}
