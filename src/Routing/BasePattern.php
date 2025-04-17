<?php
namespace Ormurin\Hull\Routing;

abstract class BasePattern
{
    protected static string $separator = '';
    protected string $regex_delimiter = '~';
    protected bool $case_sensitive = false;
    protected string $pattern = "";
    protected array $params = [];

    public function __construct(string $pattern = "", array $params = [], bool $case_sensitive = false, string $regex_delimiter = '~')
    {
        $this->setRegexDelimiter($regex_delimiter);
        $this->setCaseSensitive($case_sensitive);
        $this->setPatternString($pattern);
        $this->setParams($params);
    }

    public function setRegexDelimiter(string $delimiter): static
    {
        $delimiter = trim($delimiter);
        if ( strlen($delimiter) !== 1 || $delimiter === '\\' || ctype_alnum($delimiter) ) {
            throw new \ValueError("Invalid regex delimiter: $delimiter");
        }
        $this->regex_delimiter = $delimiter;
        return $this;
    }

    public function getRegexDelimiter(): string
    {
        return $this->regex_delimiter;
    }

    public function setCaseSensitive(bool $case_sensitive = true): static
    {
        $this->case_sensitive = $case_sensitive;
        return $this;
    }

    public function isCaseSensitive(): bool
    {
        return $this->case_sensitive;
    }

    public function setPatternString(string $pattern): static
    {
        $pattern = trim($pattern);
        $pattern = preg_quote($pattern, $this->regex_delimiter);
        foreach ( $this->params as $name => $regex ) {
            $pattern = preg_replace("~\\\\<($name)\\\\>~u", "(?<$1>$regex)", $pattern);
        }
        $this->pattern = $pattern;
        return $this;
    }

    public function getPatternString(): string
    {
        return $this->pattern;
    }

    public function setParams(array $params): static
    {
        foreach ( $params as $name => $regex ) {
            if ( !preg_match("~^[_a-zA-Z][_a-zA-Z0-9]*$~u", $name) ) {
                throw new \ValueError("Invalid pattern parameter name: $name");
            }
            if ( !is_string($regex) ) {
                throw new \InvalidArgumentException("Invalid pattern parameter: $name");
            }
            if ( !strlen($regex) ) {
                $params[$name] = ".+?";
            } else if ( $regex === '*' ) {
                $params[$name] = ".*?";
            }
        }
        $this->params = $params;
        return $this;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function matches(string $subject, array &$param_values = []): bool
    {
        return $this->match($subject, '^' . $this->pattern . '$', $param_values);
    }

    public function matchesLeft(string $subject, array &$param_values = []): bool
    {
        return $this->match($subject, '^' . $this->pattern, $param_values);
    }

    public function matchesRight(string $subject, array &$param_values = []): bool
    {
        return $this->match($subject, $this->pattern . '$', $param_values);
    }

    protected function match(string $subject, string $pattern, array &$param_values = []): bool
    {
        $param_values = [];
        if ( in_array($pattern, ['^$', '^', '$', ''], true) ) {
            return true;
        }
        $d = $this->regex_delimiter;
        $pattern = $d . $pattern . $d . 'u';
        if ( !$this->case_sensitive ) {
            $pattern .= 'i';
        }
        if ( preg_match($pattern, $subject, $param_values) ) {
            //unset($param_values[0]);
            return true;
        }
        return false;
    }

    public function getDepth(): int
    {
        if ( !strlen($this->pattern) ) {
            return 0;
        }
        if ( !strlen(static::$separator) ) {
            return 1;
        }
        return substr_count($this->pattern, static::$separator) + 1;
    }

}
