<?php

function getTimer()
{
    return new Timer();
}

class Timer
{
    public $fStart;
    public function __construct()
    {
        $this->fStart = microtime(true);
    }

    public function getElapsed()
    {
        $fElapsed = microtime(true) - $this->fStart;
        if( $fElapsed < 1 )
        {
            return round($fElapsed * 1000, 1) . 'ms';
        }
        else
        {
            return round($fElapsed, 2) . 's';
        }
    }
}