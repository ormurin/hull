<?php
namespace Ormurin\Hull\Typification;

class FloatType extends BaseType
{
    protected static string|array $name = ['float', 'double'];
    protected mixed $default_value = 0.0;

    protected function processValue(): void
    {
        $val = $this->raw_value;
        if ( is_object($val) && method_exists($val, '__toString') ) {
            $val = (string)$val;
        }
        if ( is_string($val) ) {
            $val = trim($val);
        }
        if ( is_string($val) || is_float($val) || is_int($val) || is_bool($val) || is_null($val) ) {
            $this->value = (float)$val;
        }
    }
}