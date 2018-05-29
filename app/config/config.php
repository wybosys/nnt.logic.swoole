<?php

use Nnt\Core\Config;

$cfg = [];

$cfg['server'] = [
    'port' => Config::Use(8090, 80, 80)
];

$cfg['database'] = [
    "adapter" => "Mysql",
    "host" => "develop.91egame.com",
    "username" => "root",
    "port" => "3306",
    "password" => "root",
    "dbname" => "devops",
    "charset" => "utf8"
];

$cfg['redis'] = [
    "host" => "redis",
    "port" => 6379,
    "index" => 0,
    "auth" => "root",
    "prefix" => "fp_",
    "persistent" => true
];

return new Config($cfg);