<?php
namespace Ormurin\Hull\Typification;

class StringType extends BaseType
{
    protected static string|array $name = ['string', 'str'];
    protected mixed $default_value = '';

    protected function processValue(): void
    {
        $val = $this->raw_value;
        if ( is_string($val) || is_int($val) || is_float($val) || is_null($val)
            || is_object($val) && method_exists($val, '__toString') ) {
            $this->value = (string)$val;
        } else if ( is_bool($val) ) {
            $this->value = $val ? '1' : '0';
        }
    }
}
