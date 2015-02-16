<?php

namespace Dero\Data;
use Dero\Core\Collection;

/**
 * Iterable container for multiple Columns
 * @see Column
 * @author Ryan Pallas
 */
class ColumnCollection extends  Collection
{
    /**
     * Adds a column to the collection
     * @param Column $aCol
     * @return ColumnCollection
     */
    public function Add (Column $aCol)
    {
        if($aCol instanceof Column) {
            parent::add($aCol);
        } else {
            throw new \InvalidArgumentException('Only \Dero\Core\Column may be added to '. __CLASS__);
        }
        return $this;
    }
} 