<?php

if ( PHP_SAPI === 'cli' && file_exists( __DIR__ . '/launch_cli.php') ) {
    require_once __DIR__ . '/launch_cli.php';
    return;
}

