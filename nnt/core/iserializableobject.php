<?php

namespace Nnt\Core;

interface ISerializableObject
{
    // 序列化
    function serialize(): string;

    // 反序列化
    function unserialize(string $str): bool;

}