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
 * @since         6.0.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Response;

use Cake\Http\Response;
use Psr\Http\Message\UriInterface;

/**
 * A response redirecting the client to another location.
 *
 * The response has an empty body and no `Content-Type` header.
 *
 * ### Usage
 *
 * ```php
 * return new RedirectResponse('/articles', 301, ['X-Reason' => 'moved']);
 * ```
 */
class RedirectResponse extends Response
{
    /**
     * Constructor
     *
     * @param \Psr\Http\Message\UriInterface|string $uri The location to redirect to.
     * @param int $status The redirect status code.
     * @param array<string, array<string>|string> $headers Additional headers to set.
     */
    public function __construct(UriInterface|string $uri, int $status = 302, array $headers = [])
    {
        parent::__construct(['status' => $status]);
        $this->clearHeader('Content-Type');

        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'location') {
                continue;
            }
            $this->assertHeaderName($name);
            if (strtolower($name) === 'set-cookie') {
                $value = array_merge($this->getHeader($name), (array)$value);
            }
            $this->replaceHeader($name, $this->filterHeaderValue($value));
        }
        $this->replaceHeader('Location', $this->filterHeaderValue((string)$uri));
    }
}
