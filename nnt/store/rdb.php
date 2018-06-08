<?php

namespace Nnt\Store;

abstract class Rdb extends Dbms
{
    abstract function query($cmd);

}