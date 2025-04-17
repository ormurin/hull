<?php
namespace Ormurin\Hull\Routing;

use Ormurin\Hull\Tackle\Settings;

class Roadmap
{
    protected bool $is_for_host = false;
    protected ?Settings $settings = null;
    protected ?Roadmap $parent = null;
    protected string $pattern = "";
    protected array $notfound = [];
    protected array $options = [];
    protected array $params = [];
    protected string $name = '';

    /* method => road */
    protected array $roads = [];

    public function __construct(string $pattern = "", array|object|null $settings = null, ?Roadmap $parent = null, bool $is_for_host = false)
    {
        $this->setForHost($is_for_host);
        if ( $parent !== null ) {
            $this->setParent($parent);
        }
        $this->setPattern($pattern);
        if ( $settings !== null ) {
            $this->setSettings($settings);
        }
    }

    protected function makeRoads(): void
    {
        if ( !empty($this->settings['unit']) ) {
            if ( is_string($this->settings['unit']) ) {
                $this->getRoad()->setUnit($this->settings['unit']);
            } else if ( is_array($this->settings['unit']) ) {
                foreach ( $this->settings['unit'] as $request_method => $unit ) {
                    if ( !is_string($request_method) || !is_string($unit) || !$unit ) {
                        continue;
                    }
                    $this->getRoad($request_method)->setUnit($unit);
                }
            }
        }

        if ( !empty($this->settings['controller_file']) ) {
            if ( is_string($this->settings['controller_file']) ) {
                $this->getRoad()->setControllerFile($this->settings['controller_file']);
            } else if ( is_array($this->settings['controller_file']) ) {
                foreach ( $this->settings['controller_file'] as $request_method => $controller_file ) {
                    if ( !is_string($request_method) || !is_string($controller_file) || !$controller_file ) {
                        continue;
                    }
                    $this->getRoad($request_method)->setControllerFile($controller_file);
                }
            }
        }

        if ( !empty($this->settings['controller']) ) {
            if ( is_string($this->settings['controller']) || is_object($this->settings['controller']) ) {
                $this->getRoad()->setController($this->settings['controller']);
            } else if ( is_array($this->settings['controller']) ) {
                foreach ( $this->settings['controller'] as $request_method => $controller ) {
                    if ( !is_string($request_method) || !is_string($controller) && !is_object($controller) || !$controller ) {
                        continue;
                    }
                    $this->getRoad($request_method)->setController($controller);
                }
            }
        }

        if ( !empty($this->settings['action']) ) {
            if ( is_string($this->settings['action']) || ($this->settings['action'] instanceof \Closure) ) {
                $this->getRoad()->setAction($this->settings['action']);
            } else if ( is_array($this->settings['action']) ) {
                if ( is_callable($this->settings['action'], true) ) {
                    $this->getRoad()->setAction($this->settings['action']);
                } else {
                    foreach ( $this->settings['action'] as $request_method => $action ) {
                        if ( !is_string($request_method) || !is_string($action) && !is_array($action) && !($action instanceof \Closure) || !$action ) {
                            continue;
                        }
                        if ( is_array($action) && !is_callable($action, true) ) {
                            continue;
                        }
                        $this->getRoad($request_method)->setAction($action);
                    }
                }
            }
        }

        if ( !empty($this->roads['']) ) {
            /**
             * @var Road $road
             * @var Road $default_road
             */
            $default_road = $this->roads[''];
            $default_unit = $default_road->getUnit();
            $default_action = $default_road->getAction();
            $default_controller = $default_road->getController();
            $default_controller_file = $default_road->getControllerFile();
            foreach ( $this->roads as $request_method => $road ) {
                if ( $request_method === '' ) {
                    continue;
                }
                if ( $road->getUnit() === null ) {
                    if ( $default_unit !== null ) {
                        $road->setUnit($default_unit);
                    }
                }
                if ( $road->getAction() === null ) {
                    if ( $default_action !== null ) {
                        $road->setAction($default_action);
                    }
                }
                if ( $road->getController() === null ) {
                    if ( $default_controller !== null ) {
                        $road->setController($default_controller);
                    }
                }
                if ( $road->getControllerFile() === null ) {
                    if ( $default_controller_file !== null ) {
                        $road->setControllerFile($default_controller_file);
                    }
                }
            }
        }
    }

    public function setForHost(bool $for_host = true): static
    {
        if ( $for_host && $this->parent && !$this->parent->isForHost() ) {
            throw new \LogicException("Cannot set this roadmap for host, because parent is not for host.");
        }
        $this->is_for_host = $for_host;
        return $this;
    }

    public function isForHost(): bool
    {
        return $this->is_for_host;
    }

    public function setParent(Roadmap $parent): static
    {
        if ( $this->isForHost() && !$parent->isForHost() ) {
            throw new \LogicException("This roadmap is for host, but parent is not.");
        }
        $this->parent = $parent;
        return $this;
    }

    public function getParent(): ?Roadmap
    {
        return $this->parent;
    }

    public function setPattern(string $pattern): static
    {
        $this->pattern = $pattern;
        return $this;
    }

    public function getPattern(): string
    {
        return $this->pattern;
    }

    public function getFullPattern(): string
    {
        if ( !$this->parent || $this->parent->isForHost() !== $this->is_for_host ) {
            return $this->pattern;
        }
        return $this->parent->getFullPattern() . $this->pattern;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setSettings(array|object $settings): static
    {
        if ( !($settings instanceof Settings) ) {
            if ( is_object($settings) && method_exists($settings, 'toArray') ) {
                $settings = $settings->toArray();
            }
            if ( !is_array($settings) ) {
                throw new \InvalidArgumentException("Settings must be an array or an object with toArray() method that returns an array.");
            }
            $settings = new Settings($settings);
        }
        if ( isset($settings['notfound']) && is_array($settings['notfound']) ) {
            $this->setNotfound($settings['notfound']);
            unset($settings['notfound']);
        }
        if ( isset($settings['options']) && is_array($settings['options']) ) {
            $this->setOptions($settings['options']);
            unset($settings['options']);
        }
        if ( isset($settings['params']) && is_array($settings['params']) ) {
            $this->setParams($settings['params']);
            unset($settings['params']);
        }
        if ( !empty($settings['name']) && is_string($settings['name'])) {
            $this->setName($settings['name']);
            unset($settings['name']);
        }
        $this->settings = $settings;
        $this->makeRoads();
        return $this;
    }

    public function getSettings(): ?Settings
    {
        return $this->settings;
    }

    public function setNotfound(array $notfound): static
    {
        $this->notfound = $notfound;
        return $this;
    }

    public function getNotfound(): array
    {
        return $this->notfound;
    }

    public function getCompleteNotfound(): array
    {
        if ( !$this->parent ) {
            return $this->notfound;
        }
        return array_merge($this->parent->getCompleteNotfound(), $this->notfound);
    }

    public function getNotfoundRoad(): ?Road
    {
        $notfound = $this->getCompleteNotfound();
        if ( !$notfound ) {
            return null;
        }
        return new Road($notfound);
    }

    public function setOptions(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getAllOptions(): array
    {
        if ( !$this->parent ) {
            return $this->options;
        }
        return array_merge($this->parent->getAllOptions(), $this->options);
    }

    public function setParams(array $params): static
    {
        $this->params = $params;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getAllParams(): array
    {
        if ( !$this->parent ) {
            return $this->params;
        }
        return array_merge($this->parent->getAllParams(), $this->params);
    }

    public function getUnit(string $request_method = ''): ?string
    {
        $unit = $this->getRoad($request_method, false)?->getUnit();
        if ( $unit == null && $this->parent ) {
            $unit = $this->parent->getUnit($request_method);
        }
        return $unit;
    }

    public function getControllerFile(string $request_method = ''): ?string
    {
        $controller_file = $this->getRoad($request_method, false)?->getControllerFile();
        if ( $controller_file == null && $this->parent ) {
            $controller_file = $this->parent->getControllerFile($request_method);
        }
        return $controller_file;
    }

    public function getController(string $request_method = ''): string|object|null
    {
        $controller = $this->getRoad($request_method, false)?->getController();
        if ( $controller == null && $this->parent ) {
            $controller = $this->parent->getController($request_method);
        }
        return $controller;
    }

    public function getAction(string $request_method = ''): string|array|\Closure|null
    {
        $action = $this->getRoad($request_method, false)?->getAction();
        if ( $action == null && $this->parent ) {
            $action = $this->parent->getAction($request_method);
        }
        return $action;
    }

    public function setRoad(Road $road, string $request_method = ''): static
    {
        $road->setRoadmap($this);
        $this->roads[$request_method] = $road;
        return $this;
    }

    public function getRoad(string $request_method = '', bool $create_if_not_found = true): ?Road
    {
        if ( empty($this->roads[$request_method]) && $create_if_not_found ) {
            $this->roads[$request_method] = (new Road())->setMethod($request_method)->setRoadmap($this);
        }
        if ( empty($this->roads[$request_method]) ) {
            $request_method = '';
        }
        return $this->roads[$request_method] ?? null;
    }

    public function getRoads(): array
    {
        return $this->roads;
    }

}
