<?php
namespace Ormurin\Hull\Typification;

class AbsFloatType extends FloatType
{
    protected static string|array $name = ['abs_float', 'abs_double'];

    protected function processValue(): void
    {
        parent::processValue();
        $this->value = abs($this->value);
    }
}
