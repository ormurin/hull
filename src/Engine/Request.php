<?php
namespace Ormurin\Hull\Engine;

use Ormurin\Hull\Typification\TypeFactory;
use Ormurin\Hull\Typification\ValueCase;

class Request
{
    protected string $uri = '';
    protected string $path = '';
    protected string $host = '';
    protected string $port = '';
    protected string $method = '';
    protected array $env_vars = [];
    protected array $get_vars = [];
    protected array $post_vars = [];
    protected array $files_vars = [];
    protected array $cookies_vars = [];
    protected array $session_vars = [];
    protected array $request_vars = [];
    protected ?bool $is_https = null;
    protected ?bool $is_http = null;
    protected bool $is_cli = false;
    protected bool $is_readonly = false;

    public static function fromGlobals(bool $is_readonly = false): static
    {
        $request = new static();
        if ( isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']) ) {
            $request->setUri($_SERVER['REQUEST_URI']);
            $request->setPath(strtok($_SERVER['REQUEST_URI'], '?'));
        }
        if ( isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']) ) {
            $request->setHost($_SERVER['HTTP_HOST']);
        }
        if ( isset($_REQUEST['SERVER_PORT']) && (is_string($_REQUEST['SERVER_PORT']) || is_int($_REQUEST['SERVER_PORT'])) ) {
            $request->setPort($_REQUEST['SERVER_PORT']);
            if ( $request->getPort(true) === 80 ) {
                $request->setHttp();
                $request->setHttps(false);
            } else if ( $request->getPort(true) === 443 ) {
                $request->setHttps();
                $request->setHttp(false);
            }
        }
        if ( isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD']) ) {
            $request->setMethod($_SERVER['REQUEST_METHOD']);
        }
        if ( isset($_ENV) && is_array($_ENV) ) {
            $request->setEnvVars($_ENV);
        }
        if ( isset($_GET) && is_array($_GET) ) {
            $request->setGetVars($_GET);
        }
        if ( isset($_POST) && is_array($_POST) ) {
            $request->setPostVars($_POST);
        }
        if ( isset($_FILES) && is_array($_FILES) ) {
            $request->setFilesVars($_FILES);
        }
        if ( isset($_COOKIE) && is_array($_COOKIE) ) {
            $request->setCookiesVars($_COOKIE);
        }
        if ( isset($_SESSION) && is_array($_SESSION) ) {
            $request->setSessionVars($_SESSION);
        }
        if ( defined('PHP_SAPI') && PHP_SAPI === 'cli' ) {
            $request->setCli();
            $request->setHttp(null);
        }
        if ( $is_readonly ) {
            $request->setReadOnly();
        }
        return $request;
    }

    protected function assertIsNotReadOnly(): void
    {
        if ( $this->is_readonly ) {
            throw new \LogicException("Read-only request cannot be modified.");
        }
    }

    public function setUri(string $uri): static
    {
        $this->assertIsNotReadOnly();
        $this->uri = '/' . ltrim(trim($uri), "/ \n\r\t\v\0");
        return $this;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function setPath(string $path): static
    {
        $this->assertIsNotReadOnly();
        $this->path = trim($path, "/ \n\r\t\v\0") . '/';
        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setHost(string $host): static
    {
        $this->assertIsNotReadOnly();
        $this->host = trim($host);
        return $this;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setPort(int|string $port): static
    {
        $this->assertIsNotReadOnly();
        if ( is_string($port) && $port !== '' && !preg_match("~^[1-9]\\d*$~u", $port) ) {
            throw new \ValueError("Invalid port number provided: $port");
        }
        $port = abs((int)$port);
        $this->port = (string)$port;
        return $this;
    }

    public function getPort(bool $int_val = false): string|int
    {
        if ( $int_val ) {
            return (int)$this->port;
        }
        return $this->port;
    }

    public function getUrl(bool $with_query_string = false, bool $with_protocol = true, bool $with_path = true,
                           bool $with_port = false, array $exclude_ports = [80, 443]): string
    {
        $url = '';
        if ( $with_path ) {
            $url = $with_query_string ? $this->uri : '/' . trim($this->path, "/");
        }
        if ( $this->host !== '' ) {
            $host = $this->host;
            $port = $this->getPort();
            if ( $with_port && $port !== '' && !in_array($port, $exclude_ports) ) {
                $host .= ':' . $port;
            }
            $url = $host . $url;
            if ( $with_protocol ) {
                $url = ($this->isHttp() ? 'http://' : 'https://') . $url;
            }
        }
        return $url;
    }

    public function setMethod(string $method): static
    {
        $this->assertIsNotReadOnly();
        $method = trim($method);
        if ( $method !== '' && !preg_match("~^[_a-zA-Z][_a-zA-Z0-9]*$~u", $method) ) {
            throw new \ValueError("Invalid request method provided: $method");
        }
        $this->method = strtoupper($method);
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setEnvVars(array $env_vars): static
    {
        $this->assertIsNotReadOnly();
        $this->env_vars = $env_vars;
        return $this;
    }

    public function allEnvVars(): array
    {
        return $this->env_vars;
    }

    public function setGetVars(array $get_vars): static
    {
        $this->assertIsNotReadOnly();
        $this->get_vars = $get_vars;
        return $this;
    }

    public function allGetVars(): array
    {
        return $this->get_vars;
    }

    public function setPostVars(array $post_vars): static
    {
        $this->assertIsNotReadOnly();
        $this->post_vars = $post_vars;
        return $this;
    }

    public function allPostVars(): array
    {
        return $this->post_vars;
    }

    public function setFilesVars(array $files_vars): static
    {
        $this->assertIsNotReadOnly();
        $files = [];
        foreach ( $files_vars as $file ) {
            if ( !is_array($file) || empty($file['tmp_name']) ) {
                continue;
            }
            $file_params = array_keys($file);
            if ( is_array($file['tmp_name']) ) {
                $one_file = [];
                for ( $i = 0; $i < count($file['tmp_name']); $i++ ) {
                    foreach ( $file_params as $param_name ) {
                        if ( !is_string($param_name) || !$param_name || !is_array($file[$param_name]) || !array_key_exists($i, $files[$param_name]) ) {
                            continue;
                        }
                        $one_file[$param_name] = $files[$param_name][$i];
                    }
                }
            } else {
                $one_file = $file;
            }
            if ( empty($one_file['tmp_name']) || !is_string($one_file['tmp_name']) || empty($one_file['name']) || !is_string($one_file['name'])
                || empty($one_file['type']) || !is_string($one_file['type']) || empty($one_file['size']) || !is_int($one_file['size']) ) {
                continue;
            }
            if ( empty($one_file['error']) || !is_int($one_file['error']) ) {
                $one_file['error'] = 0;
            }
            $files[] = $one_file;
        }
        $this->files_vars = $files;
        return $this;
    }

    public function allFilesVars(): array
    {
        return $this->files_vars;
    }

    public function setCookiesVars(array $cookies_vars): static
    {
        $this->assertIsNotReadOnly();
        $this->cookies_vars = $cookies_vars;
        return $this;
    }

    public function allCookiesVars(): array
    {
        return $this->cookies_vars;
    }

    public function setSessionVars(array $session_vars): static
    {
        $this->assertIsNotReadOnly();
        $this->session_vars = $session_vars;
        return $this;
    }

    public function allSessionVars(): array
    {
        return $this->session_vars;
    }

    public function setRequestVars(array $request_vars): static
    {
        $this->assertIsNotReadOnly();
        $this->request_vars = $request_vars;
        return $this;
    }

    public function allRequestVars(): array
    {
        return $this->request_vars;
    }

    public function setHttps(?bool $https = true): static
    {
        $this->assertIsNotReadOnly();
        $this->is_https = $https;
        return $this;
    }

    public function isHttps(): ?bool
    {
        return $this->is_https;
    }

    public function setHttp(?bool $http = true): static
    {
        $this->assertIsNotReadOnly();
        $this->is_http = $http;
        return $this;
    }

    public function isHttp(): ?bool
    {
        return $this->is_http;
    }

    public function setCli(bool $cli = true): static
    {
        $this->assertIsNotReadOnly();
        $this->is_cli = $cli;
        return $this;
    }

    public function isCli(): bool
    {
        return $this->is_cli;
    }

    public function setReadonly(): static
    {
        $this->is_readonly = true;
        return $this;
    }

    public function isReadonly(): bool
    {
        return $this->is_readonly;
    }

    public function env(string $name): ?string
    {
        return isset($this->env_vars[$name]) && is_string($this->env_vars[$name]) ? $this->env_vars[$name] : null;
    }

    public function cookie(string $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = isset($this->cookies_vars[$name]) && is_string($this->cookies_vars[$name]) ? $this->cookies_vars[$name] : null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function sess(string $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->session_vars[$name] ?? null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function take(string $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->post_vars[$name] ?? ($this->get_vars[$name] ?? ($this->cookies_vars[$name] ?? ($this->request_vars[$name] ?? null)));
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function fromGet(string $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->get_vars[$name] ?? null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function fromPost(string $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->post_vars[$name] ?? null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function fromRequest(string $name, ?string $type = 'trimmed_string', mixed $default = ValueCase::Default): mixed
    {
        $raw_value = $this->request_vars[$name] ?? null;
        return TypeFactory::value($raw_value, $type, $default);
    }

    public function fileData(string $name): ?array
    {
        return isset($this->file_vars[$name]) && is_array($this->file_vars[$name]) ? $this->file_vars[$name] : null;
    }

}
