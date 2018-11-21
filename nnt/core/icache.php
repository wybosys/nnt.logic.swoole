<?php

namespace Nnt\Core;

interface ICache
{
    /**
     * 从缓存读取
     * @param $key
     * @return mixed
     */
    function cacheLoad($key);

    /**
     * 保存到缓存
     * @param $key
     * @param $val
     * @param $ttl
     * @return mixed
     */
    function cacheSave($key, $val, $ttl);
}
