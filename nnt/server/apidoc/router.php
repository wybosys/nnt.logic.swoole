<?php

namespace Nnt\Server\Apidoc;

use Nnt\Component\Template;
use Nnt\Core\AbstractRouter;
use Nnt\Core\ArrayT;
use Nnt\Core\ClassT;
use Nnt\Core\Proto;
use Nnt\Core\STATUS;
use Nnt\Core\Urls;
use Nnt\Server\Devops\Permissions;
use Nnt\Server\Routers;
use Nnt\Server\Transaction;
use Nnt\Server\TransactionSubmitOption;

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
     * @boolean(1, [input, optional], "生成 logic.node 使用的api")
     */
    public $node;

    /**
     * @boolean(2, [input, optional], "生成 logic.php 使用的api")
     */
    public $php;

    /**
     * @boolean(3, [input, optional], "生成 game.h5 游戏使用api")
     */
    public $h5g;

    /**
     * @boolean(4, [input, optional], "生成 vue 项目中使用的api")
     */
    public $vue;
}


class Router extends AbstractRouter
{
    function __construct()
    {
        $this->_page = new Template();
        $this->_page->compile(file_get_contents(Urls::Expand("~/nnt/server/apidoc/apidoc.volt")));
    }

    /**
     * ApiDoc Template
     */
    private $_page;

    // 配置读取出的用来导出api的配置
    private $_routers;
    private $_models;

    function action(): string
    {
        return "api";
    }

    function config($cfg): bool
    {
        if (!parent::config($cfg))
            return false;
        $this->_routers = @$cfg->export->router;
        $this->_models = @$cfg->export->model;
        return true;
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
    function export(Transaction $trans, ExportApis $m)
    {
        if (!$m->node && !$m->php && !$m->h5g && !$m->vue) {
            $trans->status = STATUS::PARAMETER_NOT_MATCH;
            $trans->submit();
            return;
        }

        // 分析出的所有结构
        $params = [
            'domain' => Permissions::GetDomain(),
            'namespace' => "",
            'clazzes' => [],
            'enums' => [],
            'consts' => [],
            'routers' => []
        ];

        if ($m->php) {
            $sp = explode('/', $params['domain']);
            $params['namespace'] = ucfirst($sp[0]) . '\\' . ucfirst($sp[1]);
        }

        // 遍历所有模型，生成模型段
        foreach ($this->_models as $model) {
            $clazz = ClassT::Entry2Class($model);

            $info = Proto::Get($clazz);
            if ($info->hidden)
                continue;

            $clazzName = Proto::GetClassName($clazz);

            // 如果是enum
            if ($info->enum) {
                // 静态变量是用类const变量进行的模拟
                $em = [
                    'name' => $clazzName,
                    'defs' => []
                ];
                foreach (Proto::ConstsOfClass($model) as $name => $val) {
                    $em['defs'][] = [
                        'name' => $name,
                        'value' => $val
                    ];
                }
                $params['enums'][] = $em;
            } // 如果是const
            else if ($info->const) {
                foreach (Proto::ConstsOfClass($model) as $name => $val) {
                    $params['consts'][] = [
                        'name' => strtoupper($clazzName) . '_' . strtoupper($name),
                        'value' => is_string($val) ? ("\"$val\"") : $val
                    ];
                }
            } // 其他
            else {
                $clazz = [
                    'name' => $clazzName,
                    'super' => $info->super ? $info->super : "ApiModel",
                    'fields' => []
                ];
                foreach ($info->fields as $name => $field) {
                    if (!$field->input && !$field->output)
                        continue;

                    $typ = Proto::FpToTypeDef($field);
                    if ($m->php) {
                        $deco = Proto::FpToDecoDefPHP($field);
                    } else {
                        $deco = Proto::FpToDecoDef($field, 'Model.');
                    }
                    $clazz['fields'][] = [
                        'name' => $name,
                        'type' => $typ,
                        'optional' => $field->optional,
                        'file' => $field->file,
                        'enum' => $field->enum,
                        'input' => $field->input,
                        'deco' => $deco
                    ];
                }
                $params['clazzes'][] = $clazz;
            }
        }

        // 遍历所有的路由，生成接口段数据
        foreach ($this->_routers as $router) {
            $clazz = ClassT::Entry2Class($router);

            $info = \Nnt\Core\Router::Get($clazz);
            foreach ($info->actions as $name => $method) {
                if ($method->noexport)
                    continue;

                $d = [];
                $d['name'] = ucfirst($router) . ucfirst($name);
                $d['action'] = "$router.$name";

                $cn = Proto::GetClassName($method->model);
                if ($m->vue || $m->node) {
                    $d['type'] = $cn;
                } else if ($m->php) {
                    $d['type'] = 'M' . $cn;
                } else {
                    $d['type'] = "models." . $cn;
                }

                $d['comment'] = $method->comment;
                $params['routers'][] = $d;
            }
        }

        // 渲染模板
        $apis = APP_DIR . "/nnt/server/apidoc/";
        if ($m->node)
            $apis .= "apis-node.dust";
        else if ($m->h5g)
            $apis .= "apis-h5g.dust";
        else if ($m->vue)
            $apis .= "apis-vue.dust";
        else if ($m->php)
            $apis .= "apis-php.dust";

        // 数据填模板
        $dust = new \Dust\Dust();
        $tpl = $dust->compileFile($apis);
        $result = $dust->renderTemplate($tpl, $params);

        $trans->submit();
    }

    /**
     * @action(\Nnt\Core\Nil, [])
     */
    function description(Transaction $trans)
    {
        $op = new TransactionSubmitOption();
        $op->plain = json_encode([
            (array)$trans->info->headers,
            (array)$trans->info->servers,
            (array)$trans->info->gets,
            (array)$trans->info->posts,
            (array)$trans->info->requests
        ]);
        $trans->submit($op);
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
    static function RouterActions(AbstractRouter $router)
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
        return ArrayT::Convert($fps, function ($fp) {
            $t = new ParameterInfo();
            $t->name = $fp->name;
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
