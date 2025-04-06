<?php
namespace Ormurin\Hull\Routing;

class Router
{
    protected bool $use_hosts_routing = false;
    protected string $top_config_file = '';
    protected array $all_config_files = [];
    protected array $settings = [];
    protected array $roadmaps = [
        '' => []
    ];


    public function __construct(string $config_file = '', bool $use_hosts_routing = false)
    {
        $this->clean();
        $this->use_hosts_routing = $use_hosts_routing;
        $this->loadSettingsFromFile($config_file);
    }

    public function clean(): static
    {
        $this->use_hosts_routing = false;
        $this->all_config_files = [];
        $this->top_config_file = '';
        $this->settings = [];
        $this->roadmaps = [
            '' => []
        ];
        return $this;
    }

    public function setUseHostsRouting(bool $use_hosts_routing): static
    {
        $this->use_hosts_routing = $use_hosts_routing;
        return $this;
    }

    public function isUsingHostsRouting(): bool
    {
        return $this->use_hosts_routing;
    }

    public function getTopConfigFile(): string
    {
        return $this->top_config_file;
    }

    public function getAllConfigFiles(): array
    {
        return $this->all_config_files;
    }

    public function loadSettingsFromFile(string $settings_file): static
    {
        $this->settings = [];
        $this->all_config_files = [];
        $this->top_config_file = $settings_file;
        $settings = $this->includeSettings($settings_file, $this->top_config_file);
        if ( is_array($settings) ) {
            $this->fillFromSettings($settings);
        }
        return $this;
    }

    public function fillFromSettings(array $settings): static
    {
        $this->settings = $settings;
        $this->makeRoadmaps();
        return $this;
    }

    protected function makeRoadmaps(): void
    {
        foreach ( $this->settings as $pattern => $settings ) {
            if ( !is_string($pattern) ) {
                continue;
            }
            if ( is_string($settings) && $this->isUsingHostsRouting() ) {
                $settings = $this->includeSettings($settings);
            }
            if ( !is_array($settings) ) {
                continue;
            }
            $host_pattern = $this->isUsingHostsRouting() ? $pattern : '';
            $path_pattern = $this->isUsingHostsRouting() ? '' : $pattern;
            $roadmap = new Roadmap($path_pattern, $settings);
            $this->roadmaps[$host_pattern][$path_pattern] = $roadmap;
        }
    }

    protected function includeSettings(string $path, string &$real_path = ''): array|false
    {
        if ( $path === '' ) {
            return false;
        }
        if ( !is_readable($path) || !is_file($path) ) {
            throw new \ValueError("Settings file '$path' is not readable or does not exist.");
        }
        $realpath = realpath($path);
        if ( $realpath === false ) {
            throw new \RuntimeException("Cannot get the real path of settings file '$path'.");
        }
        $real_path = $realpath;
        if ( in_array($realpath, $this->all_config_files, true) ) {
            return false;
        }
        $this->all_config_files[] = $realpath;
        $settings = require $realpath;
        if ( !is_array($settings) ) {
            throw new \RuntimeException("Configuration file '$realpath' does not return an array.");
        }
        return $settings;
    }

}
