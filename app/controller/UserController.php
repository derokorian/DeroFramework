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

class UserController extends \Dero\Core\BaseController
{
    private $model;

    public function __construct(\App\Model\UserModel $oUserModel)
    {
        $this->model = $oUserModel;
        ResourceManager::AddStyle('user');
    }

    public function index()
    {

    }
}