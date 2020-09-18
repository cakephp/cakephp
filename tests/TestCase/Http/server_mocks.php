<?php
/**
 * A set of 'mocks' that replace the PHP global functions to aid testing.
 */
namespace Cake\Http;

function headers_sent()
{
    return isset($GLOBALS['mockedHeadersSent']) ? $GLOBALS['mockedHeadersSent'] : true;
}

function header($header)
{
    $GLOBALS['mockedHeaders'][] = $header;
}

if (PHP_VERSION_ID >= 70300) {
    function setcookie($name, $value, $options)
    {
        $GLOBALS['mockedCookies'][] = [
            'name' => $name,
            'value' => $value,
            'expire' => $options['expires'],
            'path' => $options['path'],
            'domain' => $options['domain'],
            'secure' => isset($options['secure']) ? $options['secure'] : false,
            'httponly' => isset($options['httponly']) ? $options['httponly'] : false,
            'samesite' => isset($options['samesite']) ? $options['samesite'] : null,
        ];
    }
} else {
    function setcookie($name, $value, $expire, $path, $domain, $secure = false, $httponly = false)
    {
        // We need to parse out sameSite for PHP < 7.3
        $samesite = null;
        if (preg_match('/^(.*); SameSite=(.*)$/', $path, $matches) === 1) {
            $path = $matches[1];
            $samesite = $matches[2];
        }

        $GLOBALS['mockedCookies'][] = compact(
            'name',
            'value',
            'expire',
            'path',
            'domain',
            'secure',
            'httponly',
            'samesite'
        );
    }
}
