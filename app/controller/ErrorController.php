<?php

namespace App\Controller;
use Dero\Core\ResourceManager;
use Dero\Core\TemplateEngine;

/**
 * Error controller
 * @author Ryan Pallas
 * @package SampleSite
 * @namespace App\Controller
 * @since 2014-02-11
 */

class ErrorController extends \Dero\Core\BaseController
{
    public function __construct()
    {
    }

    public function __destruct()
    {
    }

    public function error404()
    {
        header('HTTP/1.0 404 Not Found');
        echo TemplateEngine::LoadView('header', ['title'=>'Error']);
        echo '404 Page not found';
        echo TemplateEngine::LoadView('footer');
    }

    public function __call($func, Array $args)
    {
        if( is_numeric($func) &&
            method_exists($this, 'error' . $func) )
        {
            call_user_func([$this, 'error' . $func]);
        }
    }
}