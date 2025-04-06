<?php
namespace Ormurin\Hull\Tackle;

class StdObject implements \Iterator, \Countable, \ArrayAccess
{
    protected array $data = [];
    protected bool $is_readonly = false;
    protected string $param_delimiter = '.';

    public function __construct(array $data = [], bool $is_readonly = false, ?string $param_delimiter = null)
    {
        $this->data = $data;
        $this->is_readonly = $is_readonly;
        if ( $param_delimiter !== null ) {
            $this->setParamDelimiter($param_delimiter);
        }
    }

    public function getParamDelimiter(): string
    {
        return $this->param_delimiter;
    }

    public function setParamDelimiter(string $delimiter): static
    {
        $this->checkParamDelimiter($delimiter);
        $this->param_delimiter = $delimiter;
        return $this;
    }

    protected function checkParamDelimiter(string $delimiter): void
    {
        if ( $delimiter === '' ) {
            throw new \ValueError('Delimiter cannot be empty.');
        }
    }

    public function setReadonly(): static
    {
        $this->is_readonly = true;
        return $this;
    }

    public function isReadonly(): bool
    {
        return $this->is_readonly;
    }

    protected function assertIsNotReadonly(): void
    {
        if ( $this->is_readonly ) {
            throw new \LogicException("Cannot modify read-only object.");
        }
    }

    public function setData(array $data): static
    {
        $this->assertIsNotReadonly();
        $this->data = $data;
        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    public function __set(string $name, $value): void
    {
        $this->set($name, $value);
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }

    public function __unset(string $name): void
    {
        $this->unset($name);
    }

    public function unset(string|int $name): static
    {
        $this->assertIsNotReadonly();
        unset($this->data[$name]);
        return $this;
    }

    public function set(string|int $key, mixed $value): static
    {
        $this->assertIsNotReadonly();
        $this->data[$key] = $value;
        return $this;
    }

    public function get(string|int $key): mixed
    {
        return $this->data[$key] ?? null;
    }

    public function setValue(mixed $value, string|int ...$key): static
    {
        $this->assertIsNotReadonly();
        if ( !$key ) {
            throw new \InvalidArgumentException("No key provided.");
        }
        $data = &$this->data;
        foreach ( $key as $i => $name ) {
            if ( $data instanceof StdObject ) {
                $data->setValue($value, ...array_slice($key, $i));
                return $this;
            }
            if ( !is_array($data) ) {
                $data = [];
            }
            if ( !array_key_exists($name, $data) ) {
                $data[$name] = [];
            }
            $data = &$data[$name];
        }
        $data = $value;
        return $this;
    }

    public function delValue(string|int ...$key): static
    {
        $this->assertIsNotReadonly();
        if ( !$key ) {
            throw new \InvalidArgumentException("No key provided.");
        }
        $data = &$this->data;
        foreach ( $key as $i => $name ) {
            if ( $data instanceof StdObject ) {
                $data->delValue(...array_slice($key, $i));
                return $this;
            }
            if ( !is_array($data) || !array_key_exists($name, $data) ) {
                return $this;
            }
            if ( $i < count($key) - 1 ) {
                $data = &$data[$name];
            } else {
                unset($data[$name]);
            }
        }
        return $this;
    }

    public function getValue(string|int ...$key): mixed
    {
        $value = $this->data;
        foreach ( $key as $i => $name ) {
            if ( $value instanceof StdObject ) {
                return $value->getValue(...array_slice($key, $i));
            }
            if ( !is_array($value) || !array_key_exists($name, $value) ) {
                return null;
            }
            $value = $value[$name];
        }
        return $value;
    }

    public function setParam(string $key, mixed $value, ?string $delimiter = null): static
    {
        $this->assertIsNotReadonly();
        return $this->setValue($value, ...$this->getParamNamesFromKey($key, $delimiter));
    }

    public function delParam(string $key, ?string $delimiter = null): static
    {
        return $this->delValue(...$this->getParamNamesFromKey($key, $delimiter));
    }

    public function getParam(string $key, ?string $delimiter = null): mixed
    {
        return $this->getValue(...$this->getParamNamesFromKey($key, $delimiter));
    }

    protected function getParamNamesFromKey(string $key, ?string $delimiter = null): array
    {
        $delimiter = $delimiter ?? $this->param_delimiter;
        $this->checkParamDelimiter($delimiter);
        return explode($delimiter, $key);
    }

    public function current(): mixed
    {
        return current($this->data);
    }

    public function next(): void
    {
        next($this->data);
    }

    public function key(): string|int|null
    {
        return key($this->data);
    }

    public function valid(): bool
    {
        return key($this->data) !== null;
    }

    public function rewind(): void
    {
        reset($this->data);
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function offsetExists(mixed $offset): bool
    {
        $offset = static::getValidArrayAccessOffset($offset);
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $offset = static::getValidArrayAccessOffset($offset);
        return $this->get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $offset = static::getValidArrayAccessOffset($offset);
        $this->set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $offset = static::getValidArrayAccessOffset($offset);
        $this->unset($offset);
    }

    protected static function getValidArrayAccessOffset(mixed $offset): string|int
    {
        if ( is_object($offset) && method_exists($offset, '__toString') || is_float($offset) ) {
            $offset = (string)$offset;
        }
        if ( !is_string($offset) && !is_int($offset) ) {
            throw new \InvalidArgumentException("Array access offset must be string or integer.");
        }
        return $offset;
    }

}
