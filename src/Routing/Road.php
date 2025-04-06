<?php
namespace Ormurin\Hull\Routing;

use Ormurin\Hull\Engine\Env;
use Ormurin\Hull\Engine\Request;
use Ormurin\Hull\Tackle\Config;

class Road
{
    protected string $unit = '';
    protected string $controller_file = '';
    protected string|object $controller = '';
    protected string|array|\Closure $action = '';


    public function setUnit(string $unit): static
    {
        if ( $unit !== '' ) {
            if ( !preg_match("~^[a-zA-Z][_/a-zA-Z0-9]*$~u", $unit) ) {
                throw new \ValueError("Invalid unit name provided: $unit");
            }
            $unit = rtrim($unit, "/");
        }
        $this->unit = $unit;
        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setControllerFile(string $controller_file): static
    {
        if ( $controller_file !== '' ) {
            if ( !preg_match("~\\.php$~ui", $controller_file) ) {
                throw new \ValueError("Controller file '$controller_file' is not a valid php file.");
            }
        }
        $this->controller_file = $controller_file;
        return $this;
    }

    public function getControllerFile(): string
    {
        return $this->controller_file;
    }

    public function setController(string|object $controller): static
    {
        if ( is_string($controller) && $controller !== '' ) {
            if ( !preg_match("~^\\\\?[_a-zA-Z][\\\\_a-zA-Z0-9]*$~u", $controller) ) {
                throw new \ValueError("Invalid controller name provided: $controller");
            }
            $controller = trim($controller, "\\");
        }
        $this->controller = $controller;
        return $this;
    }

    public function getController(): string|object
    {
        return $this->controller;
    }

    public function setAction(string|array|\Closure $action): static
    {
        if ( !is_callable($action, true) ) {
            throw new \ValueError("Action must be callable.");
        }
        $this->action = $action;
        return $this;
    }

    public function getAction(): string|array|\Closure
    {
        return $this->action;
    }

    public function run(?Request $request = null, array $params = [], array $options = [], Config|array $config = []): mixed
    {
        $unitNamespace = '';
        if ( $this->unit !== '' ) {
            $unitsDir = Env::instance()->getUnitsDir();
            if ( $unitsDir === null ) {
                throw new \RuntimeException("Units directory is not set.");
            }
            $unitPath = "$unitsDir/$this->unit";
            if ( !is_readable($unitPath) || !is_dir($unitPath) ) {
                throw new \RuntimeException("Unit directory '$unitPath' is not readable or does not exist.");
            }
            Env::instance()->setHomeDir($unitPath);
        }

        return null;
    }
}
