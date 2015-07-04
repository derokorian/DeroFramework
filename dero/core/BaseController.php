<?php

namespace Dero\Core;

class BaseController
{
    /**
     * Sets the $aOpts argument to include search parameters in from the request
     *
     * @param array $aOpts
     * @param array $aParams
     */
    protected function setFilter(Array &$aOpts, Array $aParams)
    {
        if( isset($aParams['id']) )
        {
            $aOpts['id'] = $aParams['id'];
        }
        if( isset($aParams['name']) )
        {
            $aOpts['name'] = $aParams['name'];
        }
        if( isset($aParams['order']) )
        {
            $aOpts['order_by'] = $aParams['order'];
        }
        if( isset($aParams['rows']) )
        {
            $aOpts['rows'] = $aParams['rows'];
        }
        if( isset($aParams['skip']) )
        {
            $aOpts['skip'] = $aParams['skip'];
        }
    }

    /**
     * Sets the response header in the form of PROTOCOL CODE MESSAGE
     *      replaces existing response header with the mapped value
     *
     * @param int $iCode The response code to use
     */
    final protected function responseStatus($iCode)
    {
        $str = $_SERVER['SERVER_PROTOCOL']. " $iCode ";
        switch($iCode) {
            case 100:
                $str .= 'Continue'; break;
            case 101:
                $str .= 'Switching Protocols'; break;
            case 200:
                $str .= 'OK'; break;
            case 201:
                $str .= 'Created'; break;
            case 202:
                $str .= 'Accepted'; break;
            case 203:
                $str .= 'Non-Authoritative Information'; break;
            case 204:
                $str .= 'No Content'; break;
            case 205:
                $str .= 'Reset Content'; break;
            case 206:
                $str .= 'Partial Context'; break;
            case 300:
                $str .= 'Multiple Choices'; break;
            case 301:
                $str .= 'Moved Permanently'; break;
            case 302:
                $str .= 'Found'; break;
            case 303:
                $str .= 'See Other'; break;
            case 304:
                $str .= 'Not Modified'; break;
            case 305:
                $str .= 'Use Proxy'; break;
            case 307:
                $str .= 'Temporary Redirect'; break;
            case 400:
                $str .= 'Bad Request'; break;
            case 401:
                $str .= 'Unauthorized'; break;
            case 402:
                $str .= 'Payment Required'; break;
            case 403:
                $str .= 'Forbidden'; break;
            case 404:
                $str .= 'Not Found'; break;
            case 405:
                $str .= 'Method Not Allowed'; break;
            case 406:
                $str .= 'Not Acceptable'; break;
            case 407:
                $str .= 'Proxy Authentication Required'; break;
            case 408:
                $str .= 'Request Timeout'; break;
            case 409:
                $str .= 'Conflict'; break;
            case 410:
                $str .= 'Gone'; break;
            case 411:
                $str .= 'Length Required'; break;
            case 412:
                $str .= 'Precondition Failed'; break;
            case 413:
                $str .= 'Request Entity Too Large'; break;
            case 414:
                $str .= 'Request-URI Too Long'; break;
            case 415:
                $str .= 'Unsupported Media Type'; break;
            case 416:
                $str .= 'Request Range Not Satisfiable'; break;
            case 417:
                $str .= 'Expectation Failed'; break;
            case 422:
                $str .= 'Unprocessable Entity'; break;
            case 429:
                $str .= 'Too Many Requests'; break;
            case 500:
                $str .= 'Internal Server Error'; break;
            case 501:
                $str .= 'Not Implemented'; break;
            case 502:
                $str .= 'Bad Gateway'; break;
            case 503:
                $str .= 'Service Unavailable'; break;
            case 504:
                $str .= 'Gateway Timeout'; break;
            case 505:
                $str .= 'HTTP Version Not Supported'; break;
        }
        header($str, true, $iCode);
    }
}