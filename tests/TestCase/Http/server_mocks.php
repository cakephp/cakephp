<?php
declare(strict_types=1);

/**
 * A set of 'mocks' that replace the PHP global functions to aid testing.
 */
namespace Cake\Http;

function headers_sent()
{
    return $GLOBALS['mockedHeadersSent'] ?? true;
}

function header($header)
{
    $GLOBALS['mockedHeaders'][] = $header;
}

function setcookie($name, $value, $expire, $path, $domain, $secure = false, $httponly = false)
{
    $GLOBALS['mockedCookies'][] = compact(
        'name',
        'value',
        'expire',
        'path',
        'domain',
        'secure',
        'httponly'
    );
}
