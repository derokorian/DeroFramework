<?php

namespace Dero\Data;

abstract class Contract
{
    public static function fromArray(array $aProps): Contract
    {
        $s = new static();
        foreach ($aProps as $k => $v) {
            if (property_exists(get_called_class(), $k)) {
                $s->$k = $v;
            }
        }

        return $s;
    }
}