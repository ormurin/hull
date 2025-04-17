<?php
namespace Ormurin\Hull\Typification;

class MixedType extends BaseType
{
    protected static string|array $name = 'mixed';
    protected mixed $default_value = null;
    protected function processValue(): void
    {

    }
}