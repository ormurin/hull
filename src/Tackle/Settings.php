<?php
namespace Ormurin\Hull\Tackle;

class Settings extends Config
{
    protected ?Settings $parent_settings = null;
    protected string $dir = '';
    protected string $path = '';
    protected array $extensions = [];
    protected static array $default_extensions = ['.cfg.php', '.php'];

    public function __construct(string|array|object|null $path_or_data = null, string $dir = '', ?array $extensions = null,
                                bool $is_readonly = false, ?string $param_delimiter = null)
    {
        $this->setDir($dir);
        $this->setExtensions($extensions);
        if ( $path_or_data !== null ) {
            $this->setData($path_or_data);
        }
        parent::__construct(null, $is_readonly, $param_delimiter);
    }

    public static function getDataByPath(string $path, string $dir = '', string &$real_path = '', ?array $extensions = null): array
    {
        $real_path = '';
        $path = trim(rtrim($path, "/\\ \n\r\t\v\0"));
        if ( $path === '' ) {
            return [];
        }
        $dir = static::checkDirectory($dir);
        if ( $extensions === null ) {
            $extensions = self::$default_extensions;
        }
        static::checkExtensions($extensions);
        $realpath = '';
        if ( !is_readable($path) || !is_file($path) ) {
            foreach ( $extensions as $ext ) {
                $path_with_ext = $path . $ext;
                if ( is_readable($path_with_ext) && is_file($path_with_ext) ) {
                    $realpath = realpath($path_with_ext);
                    if ( $realpath === false ) {
                        throw new \RuntimeException("Cannot get real path of file '$path_with_ext'.");
                    }
                    break;
                }
            }
        }
        if ( $realpath !== '' ) {
            $real_path = str_replace('\\', '/', $realpath);
            $data = require $real_path;
            if ( is_object($data) && method_exists($data, 'toArray') ) {
                $data = $data->toArray();
            }
            if ( !is_array($data) ) {
                return [];
            }
            return $data;
        }
        if ( $dir !== '' ) {
            return self::getDataByPath($dir . '/' . $path, '', $real_path, $extensions);
        }
        return [];
    }

    protected static function checkPath(string $path): string
    {
        $path = trim(rtrim($path, "/\\ \n\r\t\v\0"));
        if ( $path === '' ) {
            return '';
        }
        if ( !is_readable($path) || !is_file($path) ) {
            throw new \RuntimeException("File '$path' is not readable or does not exist.");
        }
        $real_path = realpath($path);
        if ( $real_path === false ) {
            throw new \RuntimeException("Cannot get real path of file '$path'.");
        }
        return str_replace('\\', '/', $real_path);
    }

    protected static function checkDirectory(string $directory): string
    {
        $directory = trim(rtrim($directory, "/\\ \n\r\t\v\0"));
        if ( $directory === '' ) {
            return '';
        }
        if ( !is_readable($directory) || !is_dir($directory) ) {
            throw new \RuntimeException("Directory '$directory' is not readable or does not exist.");
        }
        $dir_path = realpath($directory);
        if ( $dir_path === false ) {
            throw new \RuntimeException("Cannot get real path of directory '$directory'.");
        }
        return str_replace('\\', '/', $dir_path);
    }

    protected static function checkExtensions(array $extensions): void
    {
        foreach ( $extensions as $extension ) {
            if ( !is_string($extension) ) {
                throw new \InvalidArgumentException("Extensions must be strings.");
            }
            if ( !preg_match("~^\\.?[_a-zA-Z][._a-zA-Z0-9]*$~u", $extension)
                || !preg_match("~[a-zA-Z0-9]$~u", $extension)
                || preg_match("~\\.{2,}~u", $extension) ) {
                throw new \ValueError("Invalid extension '$extension'.");
            }
        }
    }

    public function setExtensions(?array $extensions): static
    {
        if ( $extensions === null ) {
            $extensions = self::$default_extensions;
        }
        static::checkExtensions($extensions);
        $this->extensions = $extensions;
        return $this;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    protected function checkParentSettings(?Settings $settings): void
    {
        if ( $settings === null ) {
            return;
        }
        if ( $settings === $this ) {
            throw new \LogicException("Cannot set the same settings as parent settings.");
        }
        if ( $this->path !== '' && $this->path === $settings->getPath() ) {
            throw new \LogicException("Cannot set settings with the same path as parent settings.");
        }
        for ( $parent_settings = $settings->getParentSettings(); $parent_settings !== null; $parent_settings = $parent_settings->getParentSettings() ) {
            if ( $parent_settings === $this ) {
                throw new \LogicException("Current settings are the ancestors of the parent settings.");
            }
            if ( $this->path !== '' && $this->path === $parent_settings->getPath() ) {
                throw new \LogicException("Ancestor settings with the same path as in the current settings was found in given settings.");
            }
        }
    }

    public function setParentSettings(?Settings $settings): static
    {
        $this->checkParentSettings($settings);
        $this->parent_settings = $settings;
        return $this;
    }

    public function getParentSettings(): ?Settings
    {
        return $this->parent_settings;
    }

    public function setDir(string $dir): static
    {
        $this->dir = static::checkDirectory($dir);
        return $this;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function setPath(string $path): static
    {
        $this->path = static::checkPath($path);
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setData(array|object|string $data): static
    {
        if ( is_string($data) ) {
            $real_path = '';
            $data = static::getDataByPath($data, $this->dir, $real_path, $this->extensions);
            $this->path = $real_path;
        }
        return parent::setData($data);
    }

    public function expand(string|int ...$key): Settings
    {
        if ( !$key ) {
            throw new \InvalidArgumentException("No key provided.");
        }
        $dir = $this->dir;
        $value = $this->getValue(...$key);
        if ( $value instanceof Settings ) {
            if ( $value->getDir() === '' ) {
                $value->setDir($dir);
            }
            if  ( !$value->getParentSettings() ) {
                $value->setParentSettings($this);
            }
            return $value;
        }
        if ( is_object($value) && method_exists($value, 'toArray') ) {
            $value = $value->toArray();
        }
        if ( !is_array($value) && !is_string($value) || $value === '' ) {
            $key_str = implode($this->param_delimiter, $key);
            throw new \RuntimeException("Key '$key_str' contains no path or array.");
        }
        $real_path = '';
        if ( is_string($value) ) {
            $data = static::getDataByPath($value, $dir, $real_path, $this->extensions);
            if ( $real_path !== '' ) {
                $dir = dirname($real_path);
            }
        } else {
            $data = $value;
        }
        $settings = new Settings($data, $dir, $this->extensions, $this->is_readonly, $this->param_delimiter);
        $settings->setPath($real_path);
        $settings->setParentSettings($this);
        $this->setValue($settings, ...$key);
        return $settings;
    }

}
