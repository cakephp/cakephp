<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http;

class_alias(\Cake\Http\Client\Response::class, Response::class);

if (class_exists(Response::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\Response instead.
 */
class Response extends \Cake\Http\Client\Response
{
}
