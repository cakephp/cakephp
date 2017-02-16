<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Http\Cookie;

use Psr\Http\Message\ResponseInterface;

class ResponseCookies extends CookieCollection
{

    /**
     * Adds the cookies to the response
     *
     * @param \Psr\Http\Message\ResponseInterface $response Response object.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function addToResponse(ResponseInterface $response)
    {
        $header = [];
        foreach ($this->cookies as $setCookie) {
            $header[] = $setCookie->toHeaderValue();
        }

        return $response->withAddedHeader('Set-Cookie', $header);
    }
}
