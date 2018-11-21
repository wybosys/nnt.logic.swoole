<?php

namespace Nnt\Core;

interface IAuthUser
{
    /**
     * 返回用户的标识
     * @return string
     */
    function userIdentifier(): string;
}

class AuthUser implements IAuthUser
{
    function __construct(string $uid)
    {
        $this->_uid = $uid;
    }

    function userIdentifier(): string
    {
        return $this->_uid;
    }

    private $_uid;
}
