<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @since         3.7.0
 * @license       https://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

use Cake\Http\Response;
use Cake\Utility\CookieCryptTrait;
use Psr\Http\Message\ResponseInterface;

/**
 * CookieEncryptedEquals
 *
 * @internal
 */
class CookieEncryptedEquals extends CookieEquals
{
    use CookieCryptTrait;

    /**
     * @var \Cake\Http\Response
     */
    protected ResponseInterface $response;

    /**
     * @var string
     */
    protected string $key;

    /**
     * @var string
     */
    protected string $mode;

    /**
     * Constructor.
     *
     * @param \Cake\Http\Response|null $response A response instance.
     * @param string $cookieName Cookie name
     * @param string $mode Mode
     * @param string $key Key
     */
    public function __construct(?Response $response, string $cookieName, string $mode, string $key)
    {
        parent::__construct($response, $cookieName);

        $this->key = $key;
        $this->mode = $mode;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingNativeTypeHint
     */
    public function matches($other): bool
    {
        $cookie = $this->response->getCookie($this->cookieName);

        return $cookie !== null && $this->_decrypt($cookie['value'], $this->mode) === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf("is encrypted in cookie '%s'", $this->cookieName);
    }

    /**
     * Returns the encryption key
     *
     * @return string
     */
    protected function _getCookieEncryptionKey(): string
    {
        return $this->key;
    }
}
