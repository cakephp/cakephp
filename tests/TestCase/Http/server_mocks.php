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

function setcookie($name, $value = '', $expires = 0, $path = '', $domain = '', $secure = false, $httponly = false)
{
    if (is_array($expires)) {
        $GLOBALS['mockedCookies'][] = compact('name', 'value') + $expires;

        return;
    }

    $GLOBALS['mockedCookies'][] = compact(
        'name',
        'value',
        'expires',
        'path',
        'domain',
        'secure',
        'httponly'
    );
}
