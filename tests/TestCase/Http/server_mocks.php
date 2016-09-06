<?php
namespace Zend\Diactoros\Response;

function headers_sent()
{
    return false;
}

function header($header)
{
    $GLOBALS['mockedHeaders'][] = $header;
}
