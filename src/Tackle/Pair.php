<?php
namespace Ormurin\Hull\Tackle;

class Pair implements \ArrayAccess, \Countable
{
    protected mixed $first = null;
    protected mixed $second = null;

    public function __construct(mixed $first = null, mixed $second = null)
    {
        $this->setFirst($first);
        $this->setSecond($second);
    }

    public function setFirst(mixed $first): static
    {
        $this->first = $first;
        return $this;
    }

    public function getFirst(): mixed
    {
        return $this->first;
    }

    public function setSecond(mixed $second): static
    {
        $this->second = $second;
        return $this;
    }

    public function getSecond(): mixed
    {
        return $this->second;
    }

    public function toArray(): array
    {
        return [$this->getFirst(), $this->getSecond()];
    }

    public function __isset(string $name): bool
    {
        return $this->offsetExists($name);
    }

    public function __unset(string $name): void
    {
        $this->offsetUnset($name);
    }

    public function __set(string $name, $value): void
    {
        if ( !$this->offsetExists($name) ) {
            throw new \OutOfBoundsException("Invalid offset: $name");
        }
        $this->offsetSet($name, $value);
    }

    public function __get(string $name): mixed
    {
        if ( !$this->offsetExists($name) ) {
            throw new \OutOfBoundsException("Invalid offset: $name");
        }
        return $this->offsetGet($name);
    }

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, [0, 1, '0', '1', 'first', 'second'], true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if ( in_array($offset, [0, '0', 'first'], true) ) {
            return $this->first;
        } else if ( in_array($offset, [1, '1', 'second'], true) ) {
            return $this->second;
        }
        return null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ( in_array($offset, [0, '0', 'first'], true) ) {
            $this->first = $value;
        } else if ( in_array($offset, [1, '1', 'second'], true) ) {
            $this->second = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        if ( in_array($offset, [0, '0', 'first'], true) ) {
            $this->first = null;
        } else if ( in_array($offset, [1, '1', 'second'], true) ) {
            $this->second = null;
        }
    }

    public function count(): int
    {
        return 2;
    }

}
