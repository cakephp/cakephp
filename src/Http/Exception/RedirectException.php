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
 * @since         4.1.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Exception;

/**
 * An exception subclass used by routing and application code to
 * trigger a redirect.
 *
 * The URL and status code are provided as constructor arguments.
 *
 * ```
 * throw new RedirectException('http://example.com/some/path', 301);
 * ```
 *
 * Additional headers can also be provided in the constructor, or
 * using the setHeaders() method.
 */
class RedirectException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $target The URL to redirect to.
     * @param int $code The exception code that will be used as a HTTP status code
     * @param array<non-empty-string, array<string>|string> $headers The headers that should be sent in the unauthorized challenge response.
     */
    public function __construct(string $target, int $code = 302, array $headers = [])
    {
        parent::__construct($target, $code);

        foreach ($headers as $key => $value) {
            $this->setHeader($key, (array)$value);
        }
    }
}
