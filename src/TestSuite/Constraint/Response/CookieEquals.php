<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @since         3.7.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\TestSuite\Constraint\Response;

use Cake\Http\Response;

/**
 * CookieEquals
 *
 * @internal
 */
class CookieEquals extends ResponseBase
{
    /**
     * @var \Cake\Http\Response
     */
    protected $response;

    /**
     * @var string
     */
    protected $cookieName;

    /**
     * Constructor.
     *
     * @param \Cake\Http\Response|null $response A response instance.
     * @param string $cookieName Cookie name
     */
    public function __construct(?Response $response, string $cookieName)
    {
        parent::__construct($response);

        $this->cookieName = $cookieName;
    }

    /**
     * Checks assertion
     *
     * @param mixed $other Expected content
     * @return bool
     */
    public function matches($other): bool
    {
        $cookie = $this->response->getCookie($this->cookieName);

        return $cookie !== null && $cookie['value'] === $other;
    }

    /**
     * Assertion message
     *
     * @return string
     */
    public function toString(): string
    {
        return sprintf('is in cookie \'%s\'', $this->cookieName);
    }
}
