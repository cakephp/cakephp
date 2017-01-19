<?php
// @codingStandardsIgnoreFile
namespace Cake\Network;

use Cake\Http\ServerRequest;

class_alias(ServerRequest::class, Request::class);

if (class_exists(Request::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\ServerRequest instead
 */
class Request extends ServerRequest
{
}
