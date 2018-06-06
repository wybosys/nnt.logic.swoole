<?php

namespace Nnt\Server;

use Nnt\Core\IRouter;
use Nnt\Core\STATUS;
use Nnt\Logger\Logger;

class Routers
{
    protected $_routers = [];

    function length()
    {
        return count($this->_routers);
    }

    function register(IRouter $obj)
    {
        $actnm = $obj->action();
        if (in_array($actnm, $this->_routers)) {
            Logger::Fatal("已经注册了一个同名的路由 $actnm");
            return;
        }
        $this->_routers[$actnm] = $obj;
    }

    function find($id)
    {
        return isset($this->_routers[$id]) ? $this->_routers[$id] : null;
    }

    function process(Transaction $trans)
    {
        // 查找router
        $r = $this->find($trans->router);
        if ($r == null) {
            $trans->status = STATUS::ROUTER_NOT_FOUND;
            $trans->submit();
            return;
        }

        // 模型化
        $sta = $trans->modelize($r);
        if ($sta) {
            $trans->status = $sta;
            $trans->submit();
            return;
        }

        // 恢复数据上下文
        go(function () use ($trans) {
            $trans->collect();

            // 不做权限判断
            if (!$trans->expose) {
                // 访问权限判断
                if ($trans->needAuth()) {
                    if (!$trans->auth()) {
                        $trans->status = STATUS::NEED_AUTH;
                        $trans->submit();
                        return;
                    }
                } else {
                    go(function () use ($trans, &$pass) {
                        $pass = $this->devopscheck($trans);
                    });

                    if (!$pass) {
                        $trans->status = STATUS::PERMISSIO_FAILED;
                        $trans->submit();
                        return;
                    }
                }
            }
        });

        if (!isset($r->{$trans->call})) {
            $trans->status = STATUS::ACTION_NOT_FOUND;
            $trans->submit();
            return;
        }

        // 不论同步或者异步模式，默认认为是成功的，业务逻辑如果出错则再次设置status为对应的错误码
        $trans->status = STATUS::OK;
        $r->{$trans->call}($trans);
    }

    // devops下的权限判断
    protected function devopscheck(Transaction $trans)
    {
        return false;
    }
}
