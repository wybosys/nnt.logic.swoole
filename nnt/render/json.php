<?php

namespace Nnt\Render;

use Nnt\Server\Transaction;
use Nnt\Server\TransactionSubmitOption;

class Json implements IRender
{
    function type(): string
    {
        return "application/json";
    }

    public function render(Transaction $t, TransactionSubmitOption $opt): string
    {
        return "";
    }
}