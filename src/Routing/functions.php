<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         4.5.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
// phpcs:disable PSR1.Files.SideEffects
namespace Cake\Routing;

use Psr\Http\Message\UriInterface;

/**
 * Returns an array URL from a route path string.
 *
 * @param string $path Route path.
 * @param array $params An array specifying any additional parameters.
 *   Can be also any special parameters supported by `Router::url()`.
 * @return array URL
 * @see \Cake\Routing\Router::pathUrl()
 */
function urlArray(string $path, array $params = []): array
{
    $url = Router::parseRoutePath($path);
    $url += [
        'plugin' => false,
        'prefix' => false,
    ];

    return $url + $params;
}

/**
 * Convenience wrapper for Router::url().
 *
 * @param \Psr\Http\Message\UriInterface|array|string|null $url An array specifying any of the following:
 *   'controller', 'action', 'plugin' additionally, you can provide routed
 *   elements or query string parameters. If string it can be name any valid url
 *   string or it can be an UriInterface instance.
 * @param bool $full If true, the full base URL will be prepended to the result.
 *   Default is false.
 * @return string Full translated URL with base path.
 * @throws \Cake\Core\Exception\CakeException When the route name is not found
 * @see \Cake\Routing\Router::url()
 * @since 4.5.0
 */
function url(UriInterface|array|string|null $url = null, bool $full = false): string
{
    return Router::url($url, $full);
}
