<?php

namespace SocketProgrammingHandbook\Option;

abstract class Option
{
    abstract function isDefined();
    abstract function get();

    function getOrElse($value)
    {
        if ($this->isDefined()) {
            return $this->get();
        } else {
            return $value;
        }
    }
}

class Some extends Option implements \IteratorAggregate
{
    private $value;

    function __construct($value)
    {
        $this->value = $value;
    }

    function get()
    {
        return $this->value;
    }

    function isDefined()
    {
        return true;
    }

    function getIterator()
    {
        return new \ArrayIterator([$this->value]);
    }
}

class None extends Option implements \IteratorAggregate
{
    function get()
    {
        throw new \Exception("Cannot get value of None");
    }

    function isDefined()
    {
        return false;
    }

    function getIterator()
    {
        return new \EmptyIterator();
    }
}

function Some($value)
{
    return new Some($value);
}

function None()
{
    return new None();
}

function Option($value, $emptyValue = null)
{
    if ($value !== $emptyValue) {
        return Some($value);
    } else {
        return None();
    }
}
