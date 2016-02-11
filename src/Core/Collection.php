<?php

namespace Dero\Core;

/**
 * Class Collection
 *
 * @todo    In PHP7.1 convert to generics when they exist
 *    Collection<Type>
 *        ::add(<Type> $item)
 *        ::current() : <Type>
 *   Which will highly improve how this class works
 *     for type-hinting and remove the need to for
 *     sub-classing as we're currently doing
 *
 * @package Dero\Core
 */
class Collection implements \Iterator,
                            \Countable
{
    protected $items = [];
    protected $index = 0;

    /**
     * Adds an item to the collection
     *
     * @param mixed $item
     */
    public function add($item)
    {
        $this->items[] = $item;
    }

    /**
     * (non-PHPDoc)
     *
     * @see Iterator::current()
     */
    public function current()
    {
        return $this->items[$this->index];
    }

    /**
     * (non-PHPDoc)
     *
     * @see Iterator::key()
     */
    public function key() : int
    {
        return $this->index;
    }

    /**
     * (non-PHPDoc)
     *
     * @see Iterator::next()
     */
    public function next()
    {
        ++$this->index;
    }

    /**
     * (non-PHPDoc)
     *
     * @see Iterator::rewind()
     */
    public function rewind()
    {
        $this->index = 0;
    }

    /**
     * (non-PHPDoc)
     *
     * @see Iterator::valid()
     */
    public function valid() : bool
    {
        return isset($this->items[$this->index]);
    }

    /**
     * (non-PHPDoc)
     *
     * @see Countable::count()
     */
    public function count() : int
    {
        return count($this->items);
    }
}
