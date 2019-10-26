<?php


namespace Ajoystick;


use ArrayIterator;
use DOMNode;
use DOMNodeList;
use IteratorAggregate;
use Traversable;

/**
 * Class ElementList
 * @package Ajoystick
 */
class ElementList extends DOMNodeList implements IteratorAggregate
{
    private $elements = [];

    /**
     * ElementList constructor.
     *
     * @param DOMNodeList $nodes
     * @param Device $device
     */
    public function __construct(DOMNodeList &$nodes, Device &$device)
    {
        if ($nodes) foreach ($nodes as $node) $this->elements[] = new Element($node, $device);
    }

    /**
     * @param int $index
     * @return DOMNode|mixed|null
     */
    public function item($index)
    {
        return $this->elements[$index];
    }

    /**
     * Retrieve an external iterator
     *
     * @link https://php.net/manual/en/iteratoraggregate.getiterator.php
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     * <b>Traversable</b>
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new ArrayIterator($this->elements);
    }
}
