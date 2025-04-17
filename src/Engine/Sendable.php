<?php
namespace Ormurin\Hull\Engine;

interface Sendable
{
    public function send(): ?bool;
}
