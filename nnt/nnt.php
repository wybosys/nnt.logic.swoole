<?php

defined('PROJECT_SRC') || define('PROJECT_SRC', getcwd());

$AUTOLOADS = [];

function RegisterAutoload(string $dir)
{
    global $AUTOLOADS;
    $AUTOLOADS[] = $dir;
}

// 按照logic定义的规则，自动加载位于命名空间中的类
spl_autoload_register(function ($classname) {
    // 文件、路径均为小写
    $path = str_replace('\\', '/', strtolower($classname));
    $target = PROJECT_SRC . "/$path.php";
    if (!is_file($target)) {
        // 再尝试一次使用类名加载
        $ps = explode('\\', $classname);
        $clazz = '';
        for ($i = 0, $l = count($ps); $i < $l - 1; ++$i) {
            $clazz .= '/' . strtolower($ps[$i]);
        }
        $clazz .= '/' . $ps[$l - 1];
        $target = PROJECT_SRC . "/$clazz.php";
        if (!is_file($target)) {
            // 判断是否是在注册的查找目录中
            global $AUTOLOADS;
            $fnd = false;
            foreach ($AUTOLOADS as $dir) {
                // 全小写
                $tmp = $dir . "/$path.php";
                if (is_file($tmp)) {
                    $fnd = true;
                    $target = $tmp;
                    break;
                }
                // 路径小写
                $tmp = $dir . "/$clazz.php";
                if (is_file($tmp)) {
                    $fnd = true;
                    $target = $tmp;
                    break;
                }
                // 原始类格式
                $tmp = $dir . '/' . str_replace('\\', '/', $classname) . ".php";
                if (is_file($tmp)) {
                    $fnd = true;
                    $target = $tmp;
                    break;
                }
            }
            if (!$fnd) {
                echo "没有找到类文件 $target\n";
                return false;
            }
        }
    }
    include_once $target;
    return true;
});

RegisterAutoload(PROJECT_SRC . '/3rd');
