<?php

namespace Nnt\Server;

interface IConsoleServer
{

    // 通过控制台执行
    // @params 调用参数
    // @req 请求对象
    // @rsp 响应对象
    function invoke($params, $req, $rsp);

}