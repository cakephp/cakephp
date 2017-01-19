<?php
// @codingStandardsIgnoreFile
namespace Cake\Network\Http;

class_alias(\Cake\Http\Client\CookieCollection::class, CookieCollection::class);

if (class_exists(CookieCollection::class)) {
    return;
}

/**
 * @deprecated Use \Cake\Http\Client\CookieCollection instead.
 */
class CookieCollection extends \Cake\Http\Client\CookieCollection
{

}
