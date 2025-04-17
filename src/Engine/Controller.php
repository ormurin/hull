<?php
namespace Ormurin\Hull\Engine;

use Ormurin\Hull\Helper\PathHelper;
use Ormurin\Hull\Routing\Road;
use Ormurin\Hull\Tackle\Config;
use Ormurin\Hull\Typification\TypeFactory;
use Ormurin\Hull\Typification\ValueCase;

class Controller
{
    protected ?string $name = null;
    protected Request $request;
    protected array $path_params;
    protected array $host_params;
    protected array $options;
    protected Config $config;
    protected array $config_files = [
        "~/config/settings.cfg.php",
        "~/config/controllers/<name>.cfg.php",
    ];
    protected array $config_params = [];
    protected ?Road $road;
    protected View $view;

    public function __construct(?Request $request = null, array $path_params = [], array $host_params = [], array $options = [])
    {
        $this->setView();
        $this->setRequest($request);
        $this->setOptions($options);
        $this->setHostParams($host_params);
        $this->setPathParams($path_params);
        $this->_init_config();
        $this->_init();
    }

    protected function _init_config(): void
    {
        $settings = [];
        foreach ( $this->config_files as $config_file ) {
            if ( !is_string($config_file) ) {
                continue;
            }
            $config_file = trim(str_replace('\\', '/', $config_file), "/ \n\r\t\v\0");
            if ( $config_file === '' ) {
                continue;
            }
            $config_file = str_replace('<name>', $this->getName(), $config_file);
            if ( file_exists($config_file) ) {
                $config_file_path = $config_file;
            } else {
                $config_file_path = PathHelper::getAbsolutePath($config_file);
            }
            if ( $config_file_path !== false && is_file($config_file_path) ) {
                $settings_in_file = include $config_file_path;
                if ( is_array($settings_in_file) ) {
                    $settings = array_merge($settings, $settings_in_file);
                }
            }
        }
        $this->setConfigParams(array_merge($settings, $this->getConfigParams()));
        $this->config = new Config($this->getConfigParams());
        $this->_set_params_from_config();
    }

    protected function _set_params_from_config(): void
    {
        $this->_set_view_params_from_config();
    }

    protected function _set_view_params_from_config(): void
    {
        $html_lang = $this->config->get('html_lang');
        if ( is_string($html_lang) ) {
            $this->view->setHtmlLang($html_lang);
        }

        $head_title = $this->config->get('head_title');
        if ( is_string($head_title) ) {
            $this->view->setHeadTitle($head_title);
        }

        $head_keywords = $this->config->get('head_keywords');
        if ( is_string($head_keywords) ) {
            $this->view->setHeadKeywords($head_keywords);
        }

        $head_description = $this->config->get('head_description');
        if ( is_string($head_description) ) {
            $this->view->setHeadDescription($head_description);
        }

        $head_stylesheets = $this->config->get('head_stylesheets');
        if ( is_array($head_stylesheets) ) {
            $this->view->setHeadStylesheets($head_stylesheets);
        }

        $head_scripts = $this->config->get('head_scripts');
        if ( is_array($head_scripts) ) {
            $this->view->setHeadScripts($head_scripts);
        }

        $scripts = $this->config->get('scripts');
        if ( is_array($scripts) ) {
            foreach ( $scripts as $src => $params ) {
                if ( is_int($src) && is_string($params) ) {
                    $src = $params;
                    $params = [];
                }
                if ( !is_string($src) || !$src ) {
                    continue;
                }
                if ( !is_array($params) ) {
                    $params = [$params];
                }
                $async = in_array('async', $params, true);
                $defer = in_array('defer', $params, true);
                $head = in_array('head', $params, true);
                if ( $head ) {
                    $this->view->addHeadScript($src, $async, $defer);
                } else {
                    $this->view->addBodyScript($src, $async, $defer);
                }
            }
        }

        $layout = $this->config->get('layout');
        if ( is_string($layout) ) {
            $this->view->setLayout($layout);
        }
    }

    protected function _init(): void
    {

    }

    public function setName(?string $name): static
    {
        if ( $name !== null && (
            !preg_match("~^[_a-zA-Z][_/a-zA-Z0-9]*$~u", $name)
            || preg_match("~/[0-9]~u", $name)
            || preg_match("~/{2,}~u", $name)
            || preg_match("~/$~u", $name)) ) {
            throw new \ValueError("Invalid controller name '$name'.");
        }
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        if ( $this->name === null ) {
            $classname = static::class;
            $classname_parts = explode('\\', $classname);
            $this->name = end($classname_parts);
        }
        return $this->name;
    }

    public function setView(?View $view = null): static
    {
        if ( !$view ) {
            $view = new View();
        }
        $this->view = $view;
        return $this;
    }

    public function getView(): View
    {
        return $this->view;
    }

    public function setRoad(Road $road): static
    {
        $this->road = $road;
        return $this;
    }

    public function getRoad(): ?Road
    {
        return $this->road;
    }

    public function setRequest(?Request $request): static
    {
        if ( !$request ) {
            $request = Env::instance()->getRequest();
        }
        $this->request = $request;
        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setConfig(object|array $config): static
    {
        if ( $config instanceof Config ) {
            $this->config = $config;
            return $this;
        }
        if ( is_object($config) && method_exists($config, 'toArray') ) {
            $config = $config->toArray();
        }
        if ( !is_array($config) ) {
            throw new \InvalidArgumentException("Config must be an array or an object with toArray() method that returns an array.");
        }
        $config = new Config($config);
        $this->config = $config;
        return $this;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    public function setConfigFiles(array $config_files): static
    {
        foreach ( $config_files as $config_file ) {
            if ( !is_string($config_file) || $config_file === '' ) {
                throw new \InvalidArgumentException("Config file must be a non-empty string.");
            }
        }
        $this->config_files = $config_files;
        return $this;
    }

    public function getConfigFiles(): array
    {
        return $this->config_files;
    }

    public function setConfigParams(array $config_params): static
    {
        $this->config_params = $config_params;
        return $this;
    }

    public function getConfigParams(): array
    {
        return $this->config_params;
    }

    public function setOptions(array $options): static
    {
        if ( isset($options['_config_files_']) && is_array($options['_config_files_']) ) {
            $this->setConfigFiles($options['_config_files_']);
            unset($options['_config_files_']);
        }
        if ( isset($options['_config_params_']) && is_array($options['_config_params_']) ) {
            $this->setConfigParams($options['_config_params_']);
            unset($options['_config_params_']);
        }
        $this->options = $options;
        return $this;
    }

    public function setOption(string|int $name, mixed $value, bool $override = true): static
    {
        if ( $override || !isset($this->options[$name]) ) {
            $this->options[$name] = $value;
        }
        return $this;
    }

    public function addOptions(array $options, bool $override = false): static
    {
        foreach ( $options as $name => $value ) {
            $this->setOption($name, $value, $override);
        }
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string|int $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->options[$name] ?? null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function setHostParams(array $host_params): static
    {
        $this->host_params = $host_params;
        return $this;
    }

    public function getHostParams(): array
    {
        return $this->host_params;
    }

    public function getHostParam(string|int $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->host_params[$name] ?? null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function setPathParams(array $path_params): static
    {
        $this->path_params = $path_params;
        return $this;
    }

    public function getPathParams(): array
    {
        return $this->path_params;
    }

    public function getPathParam(string|int $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->path_params[$name] ?? null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function getParams(): array
    {
        return array_merge($this->host_params, $this->path_params);
    }

    public function getParam(string|int $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->path_params[$name] ?? ($this->host_params[$name] ?? null);
        return TypeFactory::value($raw_value, $type, $default);
    }

}
