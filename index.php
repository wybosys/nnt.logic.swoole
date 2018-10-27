<?php

// 当前文件夹设置为工作目录
chdir(__DIR__);

// 初始化框架
include "nnt/nnt.php";

// 加载入口文件
include "app/main.php";

main();
