<?php

/**
 * Blog controller
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Controller
 * @since 2013-12-06
 */

namespace App\Controller;

class BlogController
{
    private $model;

    public function __construct(\App\Model\BlogModel $oBlogModel)
    {
        $this->model = $oBlogModel;
    }

    public function index()
    {
        echo '<h2>You are viewing the blog index.</h2>';
    }

    public function viewPost($iPostId)
    {
        printf('<h2>You are viewing post number #$d</h2>', $iPostId);
    }
}