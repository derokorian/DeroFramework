<?php

namespace Dero\Data;

/**
 * Iteratable container for multiple Parameters
 * @see Parameter
 * @author Ryan Pallas
 */
class ParameterCollection implements \Iterator,
                                     \Countable
{

    private $Params = array();
    private $Index = 0;

    /**
     * Adds a parameter to the collection
     * @param Parameter $Param
     * @return ParameterCollection
     */
    public function Add (Parameter $Param)
    {
        $this->Params[] = $Param;
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::current()
     */
    public function current ()
    {
        $this->Params[$this->Index];
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::key()
     */
    public function key ()
    {
        return $this->Index;
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::next()
     */
    public function next ()
    {
        ++$this->Index;
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::rewind()
     */
    public function rewind ()
    {
        $this->Index = 0;
    }

    /**
     * (non-PHPdoc)
     * @see Iterator::valid()
     */
    public function valid ()
    {
        return isset($this->Params[$this->Index]);
    }

    /**
     * (non-PHPdoc)
     * @see Countable::count()
     */
    public function count ()
    {
        return count($this->Params);
    }
}

?>
