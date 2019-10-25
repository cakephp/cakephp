<?php
declare(strict_types=1);

/**
 * A set of 'mocks' that replace the PHP global functions to aid testing.
 */
namespace Cake\Http;

function headers_sent(&$file = null, &$line = null)
{
    return $GLOBALS['mockedHeadersSent'] ?? true;
}

function header($header)
{
    $GLOBALS['mockedHeaders'][] = $header;
}
