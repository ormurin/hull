<?php
namespace Ormurin\Hull\Typification;

class TrimmedStringType extends StringType
{
    protected static string|array $name = ['trimmed_string', 't_str'];

    protected function processValue(): void
    {
        parent::processValue();
        $this->value = trim($this->value);
    }
}