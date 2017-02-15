<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.5.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace Cake\Test\TestCase\Http\Cookie;

use Cake\Http\Client\Response;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Cookie\ResponseCookies;
use Cake\TestSuite\TestCase;

/**
 * Response Cookies Test
 */
class ResponseCookiesTest extends TestCase
{
    /**
     * testAddToResponse
     *
     * @return void
     */
    public function testAddToResponse()
    {
        $cookies = [
            new Cookie('one', 'one'),
            new Cookie('two', 'two')
        ];

        $responseCookies = new ResponseCookies($cookies);

        $response = new Response();
        $response = $responseCookies->addToResponse($response);

        $expected = [
            'Set-Cookie' => [
                'one=one',
                'two=two'
            ]
        ];
        $this->assertEquals($expected, $response->getHeaders());
    }
}
