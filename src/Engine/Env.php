<?php
namespace Ormurin\Hull\Engine;

use Ormurin\Hull\Routing\Router;
use Ormurin\Hull\Tackle\Config;

class Env
{
    protected ?string $app_dir = null;
    protected ?string $home_dir = null;
    protected ?string $work_dir = null;
    protected ?string $root_dir = null;
    protected ?string $units_dir = null;
    protected array $layout_dir_names = [];
    protected array $template_dir_names = [];
    protected string $controller_ns_part = '';
    protected string $units_namespace = '';
    protected ?Request $request = null;
    protected ?Router $router = null;
    protected ?Config $config = null;

    private static ?Env $instance = null;
    private function __construct() {}
    public static function instance(): Env
    {
        if ( self::$instance === null ) {
            self::$instance = new Env();
        }
        return self::$instance;
    }


    public function checkNamespace(string $namespace): string
    {
        $namespace = trim($namespace, "\\ \n\r\t\v\0");
        if ( $namespace !== '' ) {
            $namespace = preg_replace("~\\\\{2,}~u", '\\', $namespace);
            if ( !preg_match("~^[_a-zA-Z][\\\\_a-zA-Z0-9]*$~u", $namespace) ) {
                throw new \ValueError("Invalid namespace name provided: $namespace");
            }
        }
        return $namespace;
    }

    public function checkDir(mixed $dir): string
    {
        $dir = trim(rtrim($dir, "/\\- \n\r\t\v\0"), "\\- \n\r\t\v\0");
        if ( $dir === '' ) {
            throw new \ValueError("Directory cannot be empty.");
        }
        $dir = realpath($dir);
        if ( $dir === false ) {
            throw new \ValueError("Cannot get directory real path.");
        }
        $dir = str_replace('\\', '/', $dir);
        if ( !is_readable($dir) || !is_dir($dir) ) {
            throw new \RuntimeException("Directory '$dir' is not readable or does not exist.");
        }
        return $dir;
    }

    public function checkValidDirName(mixed $dir_name): string
    {
        if ( is_object($dir_name) && method_exists($dir_name, '__toString') || is_float($dir_name) || is_int($dir_name) ) {
            $dir_name = (string)$dir_name;
        }
        if ( !is_string($dir_name) ) {
            throw new \InvalidArgumentException("Directory name must be string.");
        }
        $dir_name = trim(rtrim($dir_name, "/\\- \n\r\t\v\0"), "\\- \n\r\t\v\0");
        if ( !preg_match("~^[-_/a-zA-Z0-9]+$~u", $dir_name) ) {
            throw new \ValueError("Directory name '$dir_name' is not a valid directory name.");
        }
        return $dir_name;
    }

    public function setAppDir(string $app_dir): Env
    {
        $this->app_dir = $this->checkDir($app_dir);
        return $this;
    }

    public function getAppDir(): ?string
    {
        return $this->app_dir;
    }

    public function setHomeDir(string $home_dir): Env
    {
        $this->home_dir = $this->checkDir($home_dir);
        return $this;
    }

    public function getHomeDir(): ?string
    {
        return $this->home_dir;
    }

    public function setWorkDir(string $work_dir): Env
    {
        $this->work_dir = $this->checkDir($work_dir);
        return $this;
    }

    public function getWorkDir(): ?string
    {
        return $this->work_dir;
    }

    public function setRootDir(string $root_dir): Env
    {
        $this->root_dir = $this->checkDir($root_dir);
        return $this;
    }

    public function getRootDir(): ?string
    {
        return $this->root_dir;
    }

    public function setUnitsDir(string $units_dir): Env
    {
        $this->units_dir = $this->checkDir($units_dir);
        return $this;
    }

    public function getUnitsDir(): ?string
    {
        return $this->units_dir;
    }

    public function setUnitsNamespace(string $namespace): Env
    {
        $this->units_namespace = $this->checkNamespace($namespace);
        return $this;
    }

    public function getUnitsNamespace(): string
    {
        return $this->units_namespace;
    }

    public function setControllersNamespacePart(string $namespace_part): Env
    {
        $this->controller_ns_part = $this->checkNamespace($namespace_part);
        return $this;
    }

    public function getControllersNamespacePart(): string
    {
        return $this->controller_ns_part;
    }

    public function setLayoutDirNames(array $dir_names): Env
    {
        foreach ( $dir_names as &$name ) {
            $name = $this->checkValidDirName($name);
        }
        $this->layout_dir_names = array_unique($dir_names);
        return $this;
    }

    public function getLayoutDirNames(): array
    {
        return $this->layout_dir_names;
    }

    public function setTemplateDirNames(array $dir_names): Env
    {
        foreach ( $dir_names as &$name ) {
            $name = $this->checkValidDirName($name);
        }
        $this->template_dir_names = array_unique($dir_names);
        return $this;
    }

    public function getTemplateDirNames(): array
    {
        return $this->template_dir_names;
    }

    public function setRequest(?Request $request): Env
    {
        $this->request = $request;
        return $this;
    }

    public function getRequest(): Request
    {
        if ( !$this->request ) {
            $this->request = Request::fromGlobals();
        }
        return $this->request;
    }

    public function setRouter(Router $router): Env
    {
        $this->router = $router;
        return $this;
    }

    public function getRouter(): ?Router
    {
        return $this->router;
    }

    public function setConfig(Config $config): Env
    {
        $this->config = $config;
        return $this;
    }

    public function getConfig(): ?Config
    {
        return $this->config;
    }

}
