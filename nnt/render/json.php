<?php

namespace Nnt\Render;

use Nnt\Core\ObjectT;
use Nnt\Core\Proto;
use Nnt\Server\Transaction;
use Nnt\Server\TransactionSubmitOption;

class Json implements IRender
{
    function type(): string
    {
        return "application/json";
    }

    public function render(Transaction $t, TransactionSubmitOption $opt = null): string
    {
        $r = null;
        if ($opt && $opt->model) {
            if ($opt->raw)
                return json_encode($t->model);
            $r = Proto::Output($t->model);
            if ($t->model && $r === null)
                $r = [];
        } else {
            $r = [
                "code" => $t->status,
                "data" => ($opt && $opt->raw) ? $t->model : Proto::Output($t->model)
            ];
            if ($t->model && $r['data'] === null)
                $r['data'] = [];
        }
        $cmid = ObjectT::Get($t->params, "_cmid");
        if ($cmid != null)
            $r["_cmid"] = $cmid;
        return json_encode($r);
    }
}