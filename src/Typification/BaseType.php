<?php
namespace Ormurin\Hull\Typification;

abstract class BaseType
{
    protected static string|array $name = [];
    protected mixed $default_value = null;
    protected mixed $raw_value = null;
    protected mixed $value = null;

    public function __construct(mixed $raw_value, mixed $default_value = ValueCase::Default)
    {
        $this->raw_value = $raw_value;
        $this->setDefaultValue($default_value);
        $this->processValue();
    }

    abstract protected function processValue(): void;

    public function setDefaultValue(mixed $value): void
    {
        if ( $value !== ValueCase::Default ) {
            $this->default_value = $value;
        }
        $this->value = $this->default_value;
    }

    public function getDefaultValue(): mixed
    {
        return $this->default_value;
    }

    public function getRawValue(): mixed
    {
        return $this->raw_value;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public static function getName(): string
    {
        if ( is_string(static::$name) ) {
            return static::$name;
        }
        foreach ( static::$name as $name ) {
            if ( is_string($name) ) {
                return $name;
            }
        }
        return '';
    }

    public static function getNames(): array
    {
        $names = static::$name;
        if ( !is_array($names) ) {
            $names = [$names];
        }
        foreach ( $names as $key => $name ) {
            if ( !is_string($name) ) {
                unset($names[$key]);
            }
        }
        return array_values($names);
    }


}
