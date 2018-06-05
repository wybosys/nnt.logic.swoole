<?php

use Nnt\Manager\App;

function main()
{
    App::LoadConfig();

    $app = new App();
    $app->start();
}