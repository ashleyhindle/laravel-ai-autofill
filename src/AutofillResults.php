<?php

namespace AshleyHindle\AiAutofill;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

class AutofillResults implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    private array $properties = [];

    public function has($key): bool
    {
        return array_key_exists($key, $this->properties);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->properties[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->properties[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->properties[] = $value;
        } else {
            $this->properties[$offset] = $value;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->properties[$offset]);
    }

    public function count(): int
    {
        return count($this->properties);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->properties);
    }

    public function jsonSerialize(): mixed
    {
        return $this->properties;
    }
}
