<?php
use Ormurin\Hull\Engine\Env;
use Ormurin\Hull\Engine\Request;
use Ormurin\Hull\Routing\Router;
use Ormurin\Hull\Tackle\Config;

Env::instance()
    ->setAppDir(APP_DIR)
    ->setRootDir(ROOT_DIR)
    ->setUnitsDir(UNITS_DIR)
    ->setUnitsNamespace(UNITS_NAMESPACE)
    ->setControllersNamespacePart(CONTROLLER_NS_PART)
    ->setTemplateDirNames(TEMPLATE_DIR_NAMES)
    ->setLayoutDirNames(LAYOUT_DIR_NAMES)
    ->setRouter(new Router(HOST_ROUTES_FILE, true))
    ->setRequest(Request::fromGlobals());

if ( file_exists(ROOT_DIR . '/config.php') ) {
    $CONFIG = require ROOT_DIR . '/config.php';
    if ( is_array($CONFIG) ) {
        Env::instance()->setConfig(new Config($CONFIG));
    }
    unset($CONFIG);
}
