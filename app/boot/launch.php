<?php
use Ormurin\Hull\Engine\Env;
use Ormurin\Hull\Engine\Response;

if ( PHP_SAPI === 'cli' && file_exists( __DIR__ . '/launch_cli.php') ) {
    $return = require_once __DIR__ . '/launch_cli.php';
    if ( $return ) {
        unset($return);
        return;
    }
    unset($return);
}

(new Response(
    Env::instance()->getRouter()->runRoad(
        Env::instance()->getRequest()
    )
))->send();
