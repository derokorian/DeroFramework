<?php

namespace Dero\Data;

use Dero\Core\Collection;
use InvalidArgumentException;

/**
 * Iterable container for multiple Parameters
 *
 * @see    Parameter
 * @author Ryan Pallas
 */
class ParameterCollection extends Collection
{
    /**
     * Adds a parameter to the collection
     *
     * @param Parameter $aParam
     *
     * @return ParameterCollection
     * @throws
     */
    public function Add($aParam)
    {
        if ($aParam instanceof Parameter) {
            parent::add($aParam);
        }
        else {
            throw new InvalidArgumentException('Only \Dero\Core\Parameter may be added to ' . __CLASS__);
        }

        return $this;
    }
}
