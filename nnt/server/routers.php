<?php

namespace Nnt\Server;

use Nnt\Core\IRouter;
use Nnt\Core\ObjectT;
use Nnt\Core\STATUS;
use Nnt\Logger\Logger;
use Nnt\Manager\Config;
use Nnt\Server\Devops\Permissions;

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
        return ObjectT::Get($this->_routers, $id);
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
        // devops环境下才进行权限判定
        if (Config::$LOCAL)
            return true;

        // 允许客户端访的将无法进行服务端权限判定
        if (Config::$CLIENT_ALLOW)
            return true;

        // 如果访问的是api.doc，则不进行判定
        if ($trans->action() == 'api.doc')
            return true;

        // 和php等一样的规则
        if (Config::$DEVOPS_DEVELOP) {
            $skip = ObjectT::Get($trans->params, Permissions::KEY_SKIPPERMISSION);
            if ($skip)
                return true;
        }

        $permid = ObjectT::Get($trans->params, Permissions::KEY_PERMISSIONID);
        if (!$permid) {
            Logger::Warn("调用接口没有传递 permissionid");
            return false;
        }

        return true;
    }
}
