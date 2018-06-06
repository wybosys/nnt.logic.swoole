<?php

namespace Nnt\Render;

use Nnt\Server\Transaction;
use Nnt\Server\TransactionSubmitOption;

class Raw implements IRender
{
    function type(): string
    {
        return 'text/plain';
    }

    public function render(Transaction $t, TransactionSubmitOption $opt): string
    {
        return (string)$t->model;
    }
}