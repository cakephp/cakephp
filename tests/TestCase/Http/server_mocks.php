<?php
declare(strict_types=1);

/**
 * A set of 'mocks' that replace the PHP global functions to aid testing.
 */
namespace Cake\Http;

function headers_sent(?string &$file = null, ?int &$line = null): bool
{
    return $GLOBALS['mockedHeadersSent'] ?? true;
}

function header(string $header, bool $replace = true, int $response_code = 0): void
{
    $GLOBALS['mockedHeaders'][] = $header;
}
