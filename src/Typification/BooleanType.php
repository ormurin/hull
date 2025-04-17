<?php
namespace Ormurin\Hull\Typification;

class BooleanType extends BaseType
{
    protected static string|array $name = ['boolean', 'bool'];
    protected mixed $default_value = false;

    protected function processValue(): void
    {
        $val = $this->raw_value;
        if ( is_object($val) && method_exists($val, '__toString') ) {
            $val = (string)$val;
        }
        if ( is_string($val) ) {
            $val = trim($val);
        }
        if ( is_string($val) && in_array(strtolower($val), ['false', 'off', 'no', '0', ''], true) ) {
            $val = false;
        }
        $this->value = (bool)$val;
    }
}