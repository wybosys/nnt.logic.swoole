<?php

namespace Nnt\Server;

class EmptyTransaction extends Transaction
{

    function waitTimeout()
    {
        // pass
    }

    function sessionId(): string
    {
        return null;
    }

    function auth(): bool
    {
        return false;
    }
}