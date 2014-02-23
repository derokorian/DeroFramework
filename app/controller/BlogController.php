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
        ResourceManager::AddScript('angular');
    }

    public function index()
    {
        $iPageSize = 5;
        $iCurPage = isset($_GET['p']) ? (int) $_GET['p'] : 1;
        $aOpts = [
            'rows' => $iPageSize,
            'skip' => $iCurPage > 1 ? ($iCurPage - 1) * $iPageSize : 0,
            'order_by' => 'created DESC',
            'published' => true
        ];
        $oRet = $this->model->getPosts($aOpts);
        if( !$oRet->HasFailure() )
        {
            if( !IS_API_REQUEST )
            {
                echo TemplateEngine::LoadView('header', ['title'=>'Index']);
            }
            foreach( $oRet->Get() as $oPost )
            {
                $oPost->modified = strtotime($oPost->modified);
                $oPost->created = strtotime($oPost->created);
                echo TemplateEngine::LoadView('blog/post', (Array)$oPost);
            }
            $oRet = $this->model->getPostCount(['published' => true]);
            if( !$oRet->HasFailure() )
            {
                $iTotalPosts = $oRet->Get();
                if( $iTotalPosts > $iPageSize )
                {
                    new Pager(
                        (int) ceil($iTotalPosts / $iPageSize),
                        $iCurPage
                    );
                }
            }
            if( !IS_API_REQUEST )
            {
                echo TemplateEngine::LoadView('footer');
            }
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

    }

    public function addPost()
    {

    }

    public function editPost($iPostId)
    {

    }

    public function deletePost()
    {

    }
}