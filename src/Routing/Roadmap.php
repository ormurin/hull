<?php
namespace Ormurin\Hull\Routing;

class Roadmap
{
    protected string $pattern = '';
    protected array $settings = [];
    protected string $name = '';
    protected array $roads = [];

    public function __construct(string $pattern = "", array $settings = [])
    {
        $this->setSettings($settings);
        $this->setPattern($pattern);
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
                if ( !$road->getUnit() && $default_unit ) {
                    $road->setUnit($default_unit);
                }
                if ( !$road->getAction() && $default_action ) {
                    $road->setAction($default_action);
                }
                if ( !$road->getController() && $default_controller ) {
                    $road->setController($default_controller);
                }
                if ( !$road->getControllerFile() && $default_controller_file ) {
                    $road->setControllerFile($default_controller_file);
                }
            }
        }
    }

    public function setSettings(array $settings): static
    {
        $this->settings = $settings;
        if ( !empty($settings['name']) && is_string($settings['name'])) {
            $this->setName($settings['name']);
        }
        $this->makeRoads();
        return $this;
    }

    public function getSettings(): array
    {
        return $this->settings;
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

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setRoad(Road $road, string $request_method = ''): static
    {
        $this->roads[$request_method] = $road;
        return $this;
    }

    public function getRoad(string $request_method = '', bool $create_if_not_found = true): ?Road
    {
        if ( empty($this->roads[$request_method]) && $create_if_not_found ) {
            $this->roads[$request_method] = new Road();
        }
        return $this->roads[$request_method] ?? null;
    }

    public function getRoads(): array
    {
        return $this->roads;
    }

}
