<?php

namespace Nnt\Server\Apidoc;

use Nnt\Component\Template;
use Nnt\Core\ArrayT;
use Nnt\Core\IRouter;
use Nnt\Core\MapT;
use Nnt\Core\Proto;
use Nnt\Core\Urls;
use Nnt\Server\Routers;
use Nnt\Server\Transaction;

class ParameterInfo
{
    public $name;
    public $string;
    public $integer;
    public $double;
    public $boolean;
    public $file;
    public $enum;
    public $array;
    public $map;
    public $object;
    public $optional;
    public $index;
    public $input;
    public $output;
    public $comment;
    public $valtyp;
    public $keytyp;
}

class ActionInfo
{
    public $name;
    public $action;
    public $comment;
    public $params = [];
}

/**
 * @model()
 */
class ExportApis
{

    /**
     * @boolean(1, [input, optional], "生成logic客户端使用的api")
     */
    public $logic;

    /**
     * @boolean(2, [input, optional], "生成h5g游戏使用api")
     */
    public $h5g;

    /**
     * @boolean(3, [input, optional], "生成vue项目中使用的api")
     */
    public $vue;
}


class Router implements IRouter
{
    function __construct()
    {
        $this->_page = new Template();
        $this->_page->compile(file_get_contents(Urls::Expand("~/nnt/server/apidoc/apidoc.volt")));
    }

    /**
     * @var Template
     */
    private $_page;

    function action(): string
    {
        return "api";
    }

    /**
     * @action(\Nnt\Core\Nil, [], "文档")
     */
    function doc(Transaction $trans)
    {
        $srv = $trans->server;
        if ($srv->routers()->length()) {
            // 收集routers的信息
            $infos = self::ActionsInfo($srv->routers());
            // 渲染页面
            $trans->output('text/html;charset=utf-8;', $this->_page->render(["actions" => json_encode($infos)]));
            return;
        }
        $trans->submit();
    }

    /**
     * @action(\Nnt\Server\Apidoc\ExportApis, [], "生成api接口文件")
     */
    function export(Transaction $trans)
    {
        $trans->submit();
    }

    /**
     * @return array ActionInfo
     */
    static function ActionsInfo(Routers $routers)
    {
        $r = [];
        $routers->foreach(function ($e) use (&$r) {
            ArrayT::PushObjects($r, self::RouterActions($e));
        });
        return $r;
    }

    // Map<string, ActionInfo[]>();
    protected static $_ActionInfos = [];

    /**
     * @return array ActionInfo
     */
    static function RouterActions(IRouter $router)
    {
        $name = $router->action();
        if (isset(self::$_ActionInfos[$name]))
            return self::$_ActionInfos[$name];

        $ri = \Nnt\Core\Router::Get($router);

        // 获得router身上的action信息以及属性列表
        $infos = $ri->infos();
        $r = ArrayT::Convert($infos, function ($info) use ($name) {
            $t = new ActionInfo();
            $t->name = $t->action = "$name.$info->name";
            $t->comment = $info->comment;
            $t->params = self::ParametersInfo($info->clazz);
            return $t;
        });
        self::$_ActionInfos[$name] = $r;
        return $r;
    }

    /**
     * @return array ParameterInfo
     */
    static function ParametersInfo($clz)
    {
        $mi = Proto::Get($clz);
        $fps = array_values($mi->fields);
        return MapT::Convert($fps, function ($fp, $name) {
            $t = new ParameterInfo();
            $t->name = $name;
            $t->array = $fp->array;
            $t->string = $fp->string;
            $t->integer = $fp->integer;
            $t->double = $fp->double;
            $t->boolean = $fp->boolean;
            $t->file = $fp->file;
            $t->enum = $fp->enum;
            $t->array = $fp->array;
            $t->map = $fp->map;
            $t->object = $fp->json;
            $t->optional = $fp->optional;
            $t->index = $fp->id;
            $t->input = $fp->input;
            $t->output = $fp->output;
            $t->comment = $fp->comment;
            $t->valtyp = $fp->valtype;
            $t->keytyp = $fp->keytype;
            return $t;
        });
    }
}