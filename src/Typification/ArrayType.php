<?php
namespace Ormurin\Hull\Typification;

class ArrayType extends BaseType
{
    protected static string|array $name = ['array', 'arr'];
    protected mixed $default_value = [];

    protected function processValue(): void
    {
        $val = $this->raw_value;
        if ( is_object($val) && method_exists($val, 'toArray') ) {
            $val = $val->toArray();
        }
        if ( is_array($val) ) {
            $this->value = $val;
        }
    }
}