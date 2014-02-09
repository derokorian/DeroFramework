<?php

namespace App\Controller;
use Dero\Core\ResourceManager;
use Dero\Core\TemplateEngine;

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
        ResourceManager::AddStyle('blog');
    }

    public function index()
    {
        $aOpts = ['rows' => 5];
        $oRet = $this->model->getPosts($aOpts);
        if( !$oRet->HasFailure() )
        {
            echo TemplateEngine::LoadView('header');
            foreach( $oRet->Get() as $oPost )
            {
                $oPost->modified = strtotime($oPost->modified);
                echo TemplateEngine::LoadView('blog/post', (Array)$oPost);
            }
            echo TemplateEngine::LoadView('footer');
        }
        else
        {
            // ToDo: Proper error handling when capabilities are built
            echo $oRet->GetError();
            var_dump($oRet->GetException());
        }
    }

    public function viewPost($iPostId)
    {
        $aOpts = ['post_id' => $iPostId];
        $aPosts = $this->model->getPosts($aOpts);
        parent::LoadView('blog/post', $aPosts[0]);
        printf('<h2>You are viewing post number #$d</h2>', $iPostId);
    }
}