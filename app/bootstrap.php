<?php
require_once __DIR__ . '/boot/const.php';
if ( defined('AUTOLOAD_FILE') && AUTOLOAD_FILE && file_exists(AUTOLOAD_FILE) ) {
    require_once AUTOLOAD_FILE;
}
require_once __DIR__ . '/boot/init_env.php';
require_once __DIR__ . '/boot/helpers.php';
require_once __DIR__ . '/boot/run_up.php';
require_once __DIR__ . '/boot/launch.php';
