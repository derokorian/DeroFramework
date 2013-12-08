<?php

/**
 * Blog controller
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Controller
 * @since 2013-12-06
 */

namespace App\Controller;

class BlogController implements \Dero\Core\ControllerInterface
{
    public function __construct(\App\Model\BlogModel $oBlogModel)
    {

    }
}