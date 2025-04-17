<?php
namespace Ormurin\Hull\Engine;

use Ormurin\Hull\Helper\PathHelper;
use Ormurin\Hull\Tackle\StdObject;

class View extends StdObject
{
    protected array $layouts = [];
    protected string $content = '';
    protected string $html_lang = 'en';
    protected string $head_title = '';
    protected ?string $head_keywords = null;
    protected ?string $head_description = null;
    protected array $head_stylesheets = [];
    protected array $head_scripts = [];
    protected array $body_scripts = [];


    public function setLayout(string|array $layout): static
    {
        if ( $layout === '' ) {
            $layout = [];
        }
        if ( !is_array($layout) ) {
            $layout = [$layout];
        }
        foreach ( $layout as $layout_path ) {
            if ( !is_string($layout_path) ) {
                throw new \InvalidArgumentException("Layout path must be string.");
            }
            if ( $layout_path === '' ) {
                throw new \ValueError("Layout path is empty.");
            }
        }
        $this->layouts = $layout;
        return $this;
    }

    public function getLayout(): string
    {
        if ( !$this->layouts ) {
            return '';
        }
        return $this->layouts[0];
    }

    public function getLayouts(): array
    {
        return $this->layouts;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function setHtmlLang(string $html_lang, bool $escape_special_chars = true): static
    {
        if ( $escape_special_chars ) {
            $html_lang = htmlspecialchars($html_lang);
        }
        $this->html_lang = $html_lang;
        return $this;
    }

    public function htmlLang(): string
    {
        return $this->html_lang;
    }

    public function setHeadTitle(string $head_title, bool $escape_special_chars = true): static
    {
        if ( $escape_special_chars ) {
            $head_title = htmlspecialchars($head_title);
        }
        $this->head_title = $head_title;
        return $this;
    }

    public function headTitle(): string
    {
        return $this->head_title;
    }

    public function setHeadKeywords(string $head_keywords, bool $escape_special_chars = true): static
    {
        if ( $escape_special_chars ) {
            $head_keywords = htmlspecialchars($head_keywords);
        }
        $this->head_keywords = $head_keywords;
        return $this;
    }

    public function headKeywords(): ?string
    {
        return $this->head_keywords;
    }

    public function setHeadDescription(string $head_description, bool $escape_special_chars = true): static
    {
        if ( $escape_special_chars ) {
            $head_description = htmlspecialchars($head_description);
        }
        $this->head_description = $head_description;
        return $this;
    }

    public function headDescription(): ?string
    {
        return $this->head_description;
    }

    public function setHeadStylesheets(array $css_paths): static
    {
        $this->head_stylesheets = [];
        if ( $css_paths ) {
            $this->addHeadStylesheet($css_paths);
        }
        return $this;
    }

    public function addHeadStylesheet(string|array $css_path): static
    {
        if ( !is_array($css_path) ) {
            $css_path = [$css_path];
        }
        foreach ( $css_path as $file_path ) {
            if ( !is_string($file_path) ) {
                throw new \InvalidArgumentException("Stylesheet path must be a string.");
            }
            if ( !str_ends_with($file_path, '.css') ) {
                throw new \ValueError("Invalid stylesheet path: $file_path");
            }
            if ( !in_array($file_path, $this->head_stylesheets) ) {
                $this->head_stylesheets[] = $file_path;
            }
        }
        return $this;
    }

    public function headStylesheets(): array
    {
        return $this->head_stylesheets;
    }

    public function setHeadScripts(array $paths, bool $async = true, bool $defer = true): static
    {
        $this->head_scripts = [];
        if ( $paths ) {
            $this->addHeadScript($paths, $async, $defer);
        }
        return $this;
    }

    public function setBodyScripts(array $paths, bool $async = true, bool $defer = true): static
    {
        $this->body_scripts = [];
        if ( $paths ) {
            $this->addBodyScript($paths, $async, $defer);
        }
        return $this;
    }

    public function addHeadScript(string|array $src, bool $async = true, bool $defer = true): static
    {
        return $this->addScript($src, true, $async, $defer);
    }

    public function addBodyScript(string|array $src, bool $async = true, bool $defer = true): static
    {
        return $this->addScript($src, false, $async, $defer);
    }

    public function addScript(string|array $src, bool $head = true, bool $async = true, bool $defer = true): static
    {
        if ( !is_array($src) ) {
            $src = [$src];
        }
        foreach ( $src as $script_path ) {
            if ( !is_string($script_path) ) {
                throw new \InvalidArgumentException("Script path must be a string.");
            }
            if ( !str_ends_with($script_path, '.js') ) {
                throw new \InvalidArgumentException("Invalid script path: $script_path");
            }
            if ( $head ) {
                if ( !in_array($script_path, $this->head_scripts) ) {
                    $this->head_scripts[$script_path] = [
                        'async' => $async,
                        'defer' => $defer
                    ];
                }
            } else {
                if ( !in_array($script_path, $this->body_scripts) ) {
                    $this->body_scripts[$script_path] = [
                        'async' => $async,
                        'defer' => $defer
                    ];
                }
            }
        }
        return $this;
    }

    public function headScripts(): array
    {
        return $this->head_scripts;
    }

    public function bodyScripts(): array
    {
        return $this->body_scripts;
    }


    public function renderJson(int $response_code = 0, bool $print_content = true,
                               int $flags = JSON_FORCE_OBJECT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT, int $depth = 512): ?string
    {
        $json = $this->toJson($flags, $depth);
        if ( $print_content ) {
            if ( $response_code > 0 ) {
                http_response_code($response_code);
            }
            header("Content-Type: application/json; charset=utf-8");
            echo $json;
            return null;
        }
        return $json;
    }

    public function render(string $template_path, bool $print_content = false, string|array|null $layout = null,
                           ?array $search_in_dirs = null, ?string $current_dir = null, int $response_code = 0): ?string
    {
        if ( $template_path === "" ) {
            throw new \ValueError("Template path is empty");
        }
        if ( is_null($search_in_dirs) ) {
            $search_in_dirs = Env::instance()->getTemplateDirNames();
        }
        if ( is_null($current_dir) ) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
            if ( !empty($backtrace[0]['file']) ) {
                $current_dir = dirname($backtrace[0]['file']);
            }
        }
        $abs_path = PathHelper::getAbsolutePath($template_path, $search_in_dirs, $current_dir);
        if ( $abs_path === false || !file_exists($abs_path) ) {
            throw new \RuntimeException("Template path not found: $template_path");
        }
        if ( is_dir($abs_path) ) {
            throw new \ValueError("Template path is a directory: $template_path");
        }
        ob_start();
        require $abs_path;
        $content = ob_get_clean();
        if ( $content === false ) {
            throw new \RuntimeException("Output buffering isn't active.");
        }
        $this->setContent($content);

        if ( is_null($layout) ) {
            $layout = $this->layouts;
        }
        if ( !is_array($layout) ) {
            $layout = strlen($layout) ? [$layout] : [];
        }
        foreach ( $layout as $layout_path ) {
            if ( !is_string($layout_path) ) {
                throw new \InvalidArgumentException("Layout path must be a string.");
            }
            $content = $this->render($layout_path, false, '', Env::instance()->getLayoutDirNames(), $current_dir);
        }

        if ( $print_content ) {
            if ( $response_code > 0 ) {
                http_response_code($response_code);
            }
            echo $content;
            return null;
        }

        return $content;
    }

}
