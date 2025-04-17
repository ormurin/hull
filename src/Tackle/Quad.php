<?php
namespace Ormurin\Hull\Tackle;

class Quad extends Trio
{
    protected mixed $fourth = null;

    public function __construct(mixed $first = null, mixed $second = null, mixed $third = null, mixed $fourth = null)
    {
        parent::__construct($first, $second, $third);
        $this->fourth = $fourth;
    }

    public function setFourth(mixed $fourth): static
    {
        $this->fourth = $fourth;
        return $this;
    }

    public function getFourth(): mixed
    {
        return $this->fourth;
    }

    public function toArray(): array
    {
        return [$this->getFirst(), $this->getSecond(), $this->getThird(), $this->getFourth()];
    }

    public function offsetExists(mixed $offset): bool
    {
        return parent::offsetExists($offset) || in_array($offset, [3, '3', 'fourth'], true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if ( in_array($offset, [3, '3', 'fourth'], true) ) {
            return $this->fourth;
        }
        return parent::offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ( in_array($offset, [3, '3', 'fourth'], true) ) {
            $this->fourth = $value;
        } else {
            parent::offsetSet($offset, $value);
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if ( in_array($offset, [3, '3', 'fourth'], true) ) {
            $this->fourth = null;
        } else {
            parent::offsetUnset($offset);
        }
    }

    public function count(): int
    {
        return 4;
    }
}