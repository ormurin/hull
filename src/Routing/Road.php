<?php
namespace Ormurin\Hull\Routing;

use Ormurin\Hull\Engine\Env;
use Ormurin\Hull\Engine\Request;

class Road
{
    protected ?Roadmap $roadmap;
    protected ?string $unit = null;
    protected ?string $controller_file = null;
    protected string|object|null $controller = null;
    protected string|array|\Closure|null $action = null;
    protected string $method = '';
    protected array $options = [];

    public function __construct(array|object $options = [], $method = '')
    {
        $this->setMethod($method);
        $this->setOptions($options);
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setOptions(array|object $options): static
    {
        if ( is_object($options) && method_exists($options, 'toArray') ) {
            $options = $options->toArray();
        }
        if ( !is_array($options) ) {
            throw new \InvalidArgumentException("Options must be an array or an object with toArray() method that returns an array.");
        }
        $this->options = $options;
        if ( isset($options['unit']) && is_string($options['unit']) ) {
            $this->setUnit($options['unit']);
        }
        if ( isset($options['controller_file']) && is_string($options['controller_file']) ) {
            $this->setControllerFile($options['controller_file']);
        }
        if ( isset($options['controller']) && (is_string($options['controller']) || is_object($options['controller'])) ) {
            $this->setController($options['controller']);
        }
        if ( isset($options['action']) && (is_string($options['action']) || is_array($options['action']) || ($options['action'] instanceof \Closure)) ) {
            $this->setAction($options['action']);
        }
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

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

    public function getUnit(bool $search_in_roadmap = false): ?string
    {
        if ( $this->unit !== null || !$this->roadmap || !$search_in_roadmap ) {
            return $this->unit;
        }
        return $this->roadmap->getUnit($this->method);
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

    public function getControllerFile(bool $search_in_roadmap = false): ?string
    {
        if ( $this->controller_file !== null || !$this->roadmap || !$search_in_roadmap ) {
            return $this->controller_file;
        }
        return $this->roadmap->getControllerFile($this->method);
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

    public function getController(bool $search_in_roadmap = false): string|object|null
    {
        if ( $this->controller !== null || !$this->roadmap || !$search_in_roadmap ) {
            return $this->controller;
        }
        return $this->roadmap->getController($this->method);
    }

    public function setAction(string|array|\Closure $action): static
    {
        if ( !is_callable($action, true) ) {
            throw new \ValueError("Action must be callable.");
        }
        $this->action = $action;
        return $this;
    }

    public function getAction(bool $search_in_roadmap = false): string|array|\Closure|null
    {
        if ( $this->action !== null || !$this->roadmap || !$search_in_roadmap ) {
            return $this->action;
        }
        return $this->roadmap->getAction($this->method);
    }

    public function setRoadmap(Roadmap $roadmap): static
    {
        $this->roadmap = $roadmap;
        return $this;
    }

    public function getRoadmap(): ?Roadmap
    {
        return $this->roadmap;
    }

    public function run(?Request $request = null, array $path_params = [], array $host_params = [], array $options = []): mixed
    {
        $result = null;

        $unitNamespace = '';
        $unit = $this->getUnit(true);
        $action = $this->getAction(true);
        $controller = $this->getController(true);
        $controllerFile = $this->getControllerFile(true);

        if ( $unit ) {
            $unitsDir = Env::instance()->getUnitsDir();
            if ( !$unitsDir ) {
                throw new \RuntimeException("Units directory is not set.");
            }
            $unitPath = "$unitsDir/$unit";
            if ( !is_readable($unitPath) || !is_dir($unitPath) ) {
                throw new \RuntimeException("Unit directory '$unitPath' is not readable or does not exist.");
            }
            Env::instance()->setHomeDir($unitPath);
            $unitNamespace = Env::instance()->getUnitsNamespace() . '\\' . str_replace('/', '\\', $unit);
        }

        if ( $controllerFile && !is_object($controller) ) {
            if ( !is_readable($controllerFile) || !is_file($controllerFile) ) {
                throw new \RuntimeException("Controller file '$controllerFile' is not readable or does not exist.");
            }
            $result = require $controllerFile;
        }

        if ( $controller && is_string($controller) ) {
            $controllerNamespace = $unitNamespace;
            $controllerNsPart = Env::instance()->getControllersNamespacePart();
            if ( $controllerNsPart ) {
                $controllerNamespace .= '\\' . $controllerNsPart;
            }
            $controllerClass = trim("$controllerNamespace\\$controller", "\\");
            if ( !class_exists($controllerClass) ) {
                $controllerClass = trim("$unitNamespace\\$controller", "\\");
                if ( !class_exists($controllerClass) ) {
                    $controllerClass = trim($controller, "\\");
                }
            }
            if ( !class_exists($controllerClass) ) {
                throw new \RuntimeException("Controller class '$controllerClass' not found.");
            }
            $controller = new $controllerClass($request, $path_params, $host_params, $options);
        }

        if ( $action ) {
            if ( is_string($action) && $controller && is_object($controller) && method_exists($controller, $action) ) {
                $result = $controller->$action();
            } else if ( is_callable($action) ) {
                $result = call_user_func($action, $request, $path_params, $host_params, $options);
            } else {
                throw new \RuntimeException("Action " . var_export($action, true) . " is not callable.");
            }
        }

        return $result;
    }
}
