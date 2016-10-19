<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http;

class_alias(\Cake\Http\Client\Request::class, Request::class);

if (class_exists(Request::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\Request instead.
 */
class Request extends \Cake\Http\Client\Request
{
}
