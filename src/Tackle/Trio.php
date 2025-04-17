<?php
namespace Ormurin\Hull\Tackle;

class Trio extends Pair
{
    protected mixed $third = null;

    public function __construct(mixed $first = null, mixed $second = null, mixed $third = null)
    {
        parent::__construct($first, $second);
        $this->setThird($third);
    }

    public function setThird(mixed $third): static
    {
        $this->third = $third;
        return $this;
    }

    public function getThird(): mixed
    {
        return $this->third;
    }

    public function toArray(): array
    {
        return [$this->getFirst(), $this->getSecond(), $this->getThird()];
    }

    public function offsetExists(mixed $offset): bool
    {
        return parent::offsetExists($offset) || in_array($offset, [2, '2', 'third'], true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if ( in_array($offset, [2, '2', 'third'], true) ) {
            return $this->third;
        }
        return parent::offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ( in_array($offset, [2, '2', 'third'], true) ) {
            $this->third = $value;
        } else {
            parent::offsetSet($offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if ( in_array($offset, [2, '2', 'third'], true) ) {
            $this->third = null;
        } else {
            parent::offsetUnset($offset);
        }
    }

    public function count(): int
    {
        return 3;
    }
}