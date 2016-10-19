<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http\Adapter;

class_alias(\Cake\Http\Client\Adapter\Stream::class, Stream::class);

if (class_exists(Stream::class)) {
    return;
}

/**
 * @deprecated Use Cake\Http\Client\Adapter\Stream instead.
 */
class Stream extends \Cake\Http\Client\Adapter\Stream
{
}
