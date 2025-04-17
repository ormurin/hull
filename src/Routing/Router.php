<?php
namespace Ormurin\Hull\Routing;

use Ormurin\Hull\Engine\Request;
use Ormurin\Hull\Tackle\Settings;

class Router
{
    protected bool $use_hosts_routing = false;
    protected string $settings_path = '';
    protected ?Settings $settings = null;

    /* host pattern => [path pattern => roadmaps] */
    protected array $roadmaps = [
        /* empty string means any host */
        '' => []
    ];

    public function __construct(array|object|string $settings_or_path = '', bool $use_hosts_routing = false)
    {
        $this->use_hosts_routing = $use_hosts_routing;
        $this->setSettings($settings_or_path);
    }

    public function clean(): static
    {
        $this->settings_path = '';
        $this->settings = null;
        $this->roadmaps = [
            '' => []
        ];
        return $this;
    }

    public function useHostsRouting(bool $use_hosts_routing = true): static
    {
        $this->use_hosts_routing = $use_hosts_routing;
        return $this;
    }

    public function isUsingHostsRouting(): bool
    {
        return $this->use_hosts_routing;
    }

    public function setSettings(array|object|string $settings_or_path): static
    {
        $this->clean();
        $settings = $settings_or_path;
        if ( is_object($settings) ) {
            if ( method_exists($settings, 'toArray') ) {
                $settings = $settings->toArray();
            }
            if ( !is_array($settings) ) {
                throw new \InvalidArgumentException("Settings should be an array or an object with toArray() method that returns an array.");
            }
        } else if ( is_string($settings) ) {
            return $this->loadSettingsByPath($settings);
        }
        $this->fillFromSettings($settings);
        return $this;
    }

    public function getSettings(): ?Settings
    {
        return $this->settings;
    }

    public function getSettingsPath(): string
    {
        return $this->settings_path;
    }

    public function loadSettingsByPath(string $settings_path): static
    {
        $this->settings_path = $settings_path;
        $settings = $this->includeSettings($settings_path, $this->settings_path);
        $settings_dir = '';
        if ( $this->settings_path !== '' ) {
            $settings_dir = str_replace('\\', '/', dirname($this->settings_path));
        }
        $this->fillFromSettings($settings, $settings_dir, $this->settings_path);
        return $this;
    }

    public function fillFromSettings(array|object $settings, string $settings_dir = '', string $settings_path = ''): static
    {
        $extensions = null;
        if ( is_object($settings) && method_exists($settings, 'toArray') ) {
            if ( $settings instanceof Settings ) {
                $extensions = $settings->getExtensions();
            }
            $settings = $settings->toArray();
        }
        if ( !is_array($settings) ) {
            throw new \InvalidArgumentException("Settings should be an array or an object with toArray() method that returns an array.");
        }
        $this->settings = new Settings($settings, $settings_dir, $extensions);
        $this->settings->setPath($settings_path);
        $this->makeRoadmaps();
        return $this;
    }

    protected function makeRoadmaps(?Roadmap $parentRoadmap = null): void
    {
        $host = '';
        $parent_pattern = '';
        if ( $parentRoadmap ) {
            $is_for_host = false;
            $settings = $parentRoadmap->getSettings()->getValue('paths');
            if ( !($settings instanceof Settings) ) {
                if ( is_array($settings) ) {
                    $settings = new Settings($settings, $parentRoadmap->getSettings()->getDir());
                } else {
                    throw new \LogicException("Paths settings should be an array or instance of " . Settings::class);
                }
            }
            if ( $parentRoadmap->isForHost() ) {
                $host = $parentRoadmap->getPattern();
            } else {
                $parent_pattern = $parentRoadmap->getPattern();
            }
        } else {
            $is_for_host = $this->isUsingHostsRouting();
            $settings = $this->settings;
        }
        if ( !$settings ) {
            return;
        }
        $settings->reverse();

        foreach ( $settings as $pattern => $patternSettings ) {
            if ( !is_string($pattern) ) {
                $settings->delValue($pattern);
                continue;
            }

            if ( is_string($patternSettings) && $patternSettings !== '' ) {
                $patternSettings = [
                    'paths' => $patternSettings,
                ];
                $settings->setValue($patternSettings, $pattern);
            }

            if ( is_array($patternSettings) || is_object($patternSettings) && method_exists($patternSettings, 'toArray') ) {
                $patternSettings = $settings->expand($pattern);
            } else {
                $settings->delValue($pattern);
                continue;
            }

            $path = '';
            if ( $is_for_host ) {
                $host = $pattern;
            } else {
                $path = $parent_pattern . $pattern;
            }

            $childPaths = $patternSettings->getValue('paths');
            if ( is_string($childPaths) && $childPaths !== ''
                || is_object($childPaths) && method_exists($childPaths, 'toArray')
                || is_array($childPaths) ) {
                $patternSettings->expand('paths');
            } else {
                $childPaths = false;
            }

            $roadmap = new Roadmap($pattern, $patternSettings, $parentRoadmap, $is_for_host);

            if ( $childPaths ) {
                $this->makeRoadmaps($roadmap);
            }

            $patternSettings->delValue('paths');

            $this->setRoadmap($roadmap, $path, $host);
        }
    }

    public function includeSettings(string $path, string &$real_path = ''): array
    {
        if ( $path === '' ) {
            return [];
        }
        if ( !is_readable($path) || !is_file($path) ) {
            throw new \RuntimeException("Settings file '$path' is not readable or does not exist.");
        }
        $realpath = realpath($path);
        if ( $realpath === false ) {
            throw new \RuntimeException("Cannot get the real path of '$path' settings file.");
        }
        $real_path = str_replace('\\', '/', $realpath);
        $settings = require $real_path;
        if ( is_object($settings) && method_exists($settings, 'toArray') ) {
            $settings = $settings->toArray();
        }
        if ( !is_array($settings) ) {
            throw new \RuntimeException("Settings file '$realpath' does not return an array.");
        }
        return $settings;
    }

    public function setRoadmap(Roadmap $roadmap, string $path_pattern, string $host_pattern = ''): static
    {
        if ( !isset($this->roadmaps[$host_pattern]) || !is_array($this->roadmaps[$host_pattern]) ) {
            $this->roadmaps[$host_pattern] = [];
        }
        $this->roadmaps[$host_pattern][$path_pattern] = $roadmap;
        return $this;
    }

    public function getRoadmap(string $path_pattern, string $host_pattern = ''): ?Roadmap
    {
        if ( isset($this->roadmaps[$host_pattern][$path_pattern]) ) {
            if ( !($this->roadmaps[$host_pattern][$path_pattern] instanceof Roadmap) ) {
                throw new \RuntimeException("Roadmap ['$host_pattern' : '$path_pattern'] is not a " . Roadmap::class . " object.");
            }
            return $this->roadmaps[$host_pattern][$path_pattern];
        }
        return null;
    }

    public function getRoadmapParams(string $path_pattern, string $host_pattern = ''): array
    {
        $roadmap = $this->getRoadmap($path_pattern, $host_pattern);
        if ( !$roadmap ) {
            return [];
        }
        return $roadmap->getParams();
    }

    public function getHostRoadmapParams(string $host_pattern = ''): array
    {
        return $this->getRoadmapParams('', $host_pattern);
    }

    public function getHostRoadmap(string $host_pattern = ''): ?Roadmap
    {
        return $this->getRoadmap('', $host_pattern);
    }

    public function getHostRoadmaps(string $host_pattern = ''): array
    {
        if ( !isset($this->roadmaps[$host_pattern]) || !is_array($this->roadmaps[$host_pattern]) ) {
            return [];
        }
        $roadmaps = $this->roadmaps[$host_pattern];
        foreach ( $roadmaps as $path_pattern => $roadmap ) {
            if ( !is_string($path_pattern) || !($roadmap instanceof Roadmap) ) {
                unset($roadmaps[$path_pattern]);
            }
        }
        return $roadmaps;
    }

    public function getHostPatterns(): array
    {
        return array_filter(array_keys($this->roadmaps), function ($host_pattern) {
            return is_string($host_pattern) && is_array($this->roadmaps[$host_pattern]);
        });
    }

    public function getPathPatterns(string $host_pattern = ''): array
    {
        if ( !isset($this->roadmaps[$host_pattern]) || !is_array($this->roadmaps[$host_pattern]) ) {
            return [];
        }
        return array_filter(array_keys($this->roadmaps[$host_pattern]), function ($path_pattern) use ($host_pattern) {
            return is_string($path_pattern) && ($this->roadmaps[$host_pattern][$path_pattern] instanceof Roadmap);
        });
    }

    public function findHostPatternByHost(string $host, bool &$matches_completely = false, array &$host_params = []): string|false
    {
        $host_params = [];
        $matches_completely = false;
        /* depth => pattern */
        $host_patterns = [];
        foreach ( $this->getHostPatterns() as $pattern ) {
            $param_values = [];
            $hostPattern = new HostPattern($pattern, $this->getHostRoadmapParams($pattern));
            if ( $hostPattern->matches($host, $param_values) ) {
                $host_params = $param_values;
                $matches_completely = true;
                return $pattern;
            } else if ( $hostPattern->matchesRight($host, $param_values) ) {
                $host_params = $param_values;
                $host_patterns[$hostPattern->getDepth()] = $pattern;
            }
        }
        if ( !$host_patterns ) {
            return false;
        }
        ksort($host_patterns, SORT_NUMERIC);
        return end($host_patterns);
    }

    public function findPathPatternByPath(string $path, string $host_pattern, bool &$matches_completely = false, array &$path_params = []): string|false
    {
        $path_params = [];
        $matches_completely = false;
        /* depth => pattern */
        $path_patterns = [];
        foreach ( $this->getPathPatterns($host_pattern) as $pattern ) {
            $param_values = [];
            $pathPattern = new PathPattern($pattern, $this->getRoadmapParams($pattern, $host_pattern));
            if ( $pathPattern->matches($path, $param_values) ) {
                $path_params = $param_values;
                $matches_completely = true;
                return $pattern;
            } else if ( $pathPattern->matchesLeft($path, $param_values) ) {
                $path_params = $param_values;
                $path_patterns[$pathPattern->getDepth()] = $pattern;
            }
        }
        if ( !$path_patterns ) {
            return false;
        }
        ksort($path_patterns, SORT_NUMERIC);
        return end($path_patterns);
    }

    public function findRoadmapByRequest(Request $request, array &$path_params = [], array &$host_params = [],
                                         bool &$path_found = false, bool &$host_found = false): ?Roadmap
    {
        $roadmap = null;
        $host_found = false;
        $path_found = false;

        $path = $request->getPath();
        $host = $request->getHost();
        if ( !$this->use_hosts_routing ) {
            $host = '';
        }

        $host_params = [];
        $path_params = [];
        $matches_completely = false;

        $host_pattern = $this->findHostPatternByHost($host, $matches_completely, $host_params);
        if ( $host_pattern !== false ) {
            $roadmap = $this->getHostRoadmap($host_pattern);
            if ( $matches_completely ) {
                $host_found = true;
            }
        }
        if ( !$host_found ) {
            return $roadmap;
        }

        $path_pattern = $this->findPathPatternByPath($path, $host_pattern, $matches_completely, $path_params);
        if ( $path_pattern !== false ) {
            $roadmap = $this->getRoadmap($path_pattern, $host_pattern);
            if ( $matches_completely ) {
                $path_found = true;
            }
        }

        return $roadmap;
    }

    public function runRoad(Request $request): mixed
    {
        $host_found = false;
        $path_found = false;
        $host_params = [];
        $path_params = [];
        $roadmap = $this->findRoadmapByRequest($request, $path_params, $host_params, $path_found, $host_found);
        if ( $roadmap ) {
            $method = $request->getMethod();
            $road = $path_found ? $roadmap->getRoad($method, false) : $roadmap->getNotfoundRoad();
            if ( $road ) {
                return $road->run($request, $path_params, $host_params, $roadmap->getAllOptions());
            }
        }
        return null;
    }

}
