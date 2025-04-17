<?php
namespace Ormurin\Hull\Typification;

class AbsIntegerType extends IntegerType
{
    protected static string|array $name = ['abs_integer', 'abs_int'];

    protected function processValue(): void
    {
        parent::processValue();
        $this->value = abs($this->value);
    }
}
