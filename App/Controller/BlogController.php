<?php

namespace App\Controller;

/**
 * Blog controller
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Controller
 * @since 2013-12-06
 */

class BlogController extends \Dero\Core\BaseController
{
    private $model;

    public function __construct(\App\Model\BlogModel $oBlogModel)
    {
        $this->model = $oBlogModel;
    }

    public function index()
    {
        $aOpts = ['rows' => 5];
        $aPosts = $this->model->getPosts($aOpts);
        foreach( $aPosts as $oPost )
        {
            parent::LoadView('blog/post', $oPost);
        }
        echo '<h2>You are viewing the blog index.</h2>';
    }

    public function viewPost($iPostId)
    {
        $aOpts = ['post_id' => $iPostId];
        $aPosts = $this->model->getPosts($aOpts);
        parent::LoadView('blog/post', $aPosts[0]);
        printf('<h2>You are viewing post number #$d</h2>', $iPostId);
    }
}