<?php

namespace Dero\Controller;

class BaseController
{
    protected static $response_status_messages = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Context',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Request Range Not Satisfiable',
        417 => 'Expectation Failed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    /**
     * Sets the $aOpts argument to include search parameters in from the request
     *
     * @param array $aOpts
     * @param int $iInput
     */
    protected function setFilter(Array &$aOpts, int $iInput)
    {
        if (filter_has_var($iInput, 'id')) {
            $aOpts['id'] = filter_input($iInput, 'id', FILTER_VALIDATE_INT);
        }
        if (filter_has_var($iInput, 'name')) {
            $aOpts['name'] = filter_input($iInput, 'name', FILTER_SANITIZE_STRING);
        }
        if (filter_has_var($iInput, 'order')) {
            $aOpts['order_by'] = filter_input($iInput, 'order', FILTER_SANITIZE_STRING);
        }
        if (filter_has_var($iInput, 'rows')) {
            $aOpts['rows'] = filter_input($iInput, 'rows', FILTER_VALIDATE_INT);
        }
        if (filter_has_var($iInput, 'skip')) {
            $aOpts['skip'] = filter_input($iInput, 'skip', FILTER_VALIDATE_INT);
        }
    }

    /**
     * Sets the response header in the form of PROTOCOL CODE MESSAGE
     *      replaces existing response header with the mapped value
     *
     * @param int $iCode The response code to use
     */
    final protected function responseStatus(int $iCode)
    {
        if (!isset(static::$response_status_messages[$iCode])) {
            throw new \InvalidArgumentException("$iCode is not a recognized response status");
        }

        $str = filter_input(INPUT_SERVER, 'SERVER_PROTOCOL', FILTER_SANITIZE_STRING);
        $str .= " $iCode " . static::$response_status_messages[$iCode];

        if (!headers_sent()) {
            header($str, true, $iCode);
        }
    }
}
